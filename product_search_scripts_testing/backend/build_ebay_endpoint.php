<?php
/**
 * build_ebay_endpoint.php
 * Cleaned up helper to build eBay Browse API search endpoints properly.
 */

/**
 * Constructs the brand list lookup endpoint for a given category.
 *
 * @param int|string $categoryId
 * @return string
 */
function construct_brand_list_endpoint($categoryId) {
    return "https://api.ebay.com/buy/browse/v1/item_summary/search?q=&category_ids={$categoryId}&fieldgroups=ASPECT_REFINEMENTS";
}

/**
 * Extracts the list of recognized brands from the eBay aspect refinements response.
 *
 * @param string $response Raw JSON string returned from eBay Browse API
 * @return array List of brand names (strings)
 */
function extract_brands_from_response($response) {
    $brands = [];

    if (empty($response)) return $brands;

    // If it's already decoded, skip json_decode
    if (is_string($response)) {
        $data = json_decode($response, true);
    } else {
        $data = $response;  // already an array or stdClass
    }

    if (!isset($data['refinement']['aspectDistributions'])) return $brands;

    foreach ($data['refinement']['aspectDistributions'] as $aspect) {
        if ($aspect['localizedAspectName'] === 'Brand') {
            foreach ($aspect['aspectValueDistributions'] as $value) {
                $brands[] = $value['localizedAspectValue'];
            }
        }
    }

    return $brands;
}

/**
 * eBay Browse endpoint builder.
 *
 * $keywords     Free-text search terms (will go to q; NO boolean operators added).
 * $categoryId   Deepest eBay category id (string|int|null). If null/empty, omitted.
 * $filters      Assoc array: 'Filter Name' => [ 'Val1', 'Val2', ... ]
 * $options      Optional assoc:
 *   - 'sort'               => 'price'|'price_asc'|'price_desc'|'-price'
 *   - 'condition'          => 'New'|'Used'
 *   - 'min_price'          => number
 *   - 'max_price'          => number
 *   - 'limit'              => int (default 50)
 *   - 'offset'             => int (default 0)
 *   - 'recognized_brands'  => [ 'Ashcroft','WIKA', ... ]  // optional per-category list
 *   - 'aspect_map'         => [ 'UI Name' => 'eBay Aspect Name', ... ]
 */
function build_ebay_endpoint(string $keywords, $categoryId = null, array $filters = [], array $options = []) : string
{
    $apiBase = 'https://api.ebay.com/buy/browse/v1/item_summary/search?';

    // --- Options / defaults ---
    $recognizedBrands = isset($options['recognized_brands']) && is_array($options['recognized_brands'])
        ? $options['recognized_brands'] : [];

    // Map your UI filter names -> eBay aspect names (tweak/extend as needed)
    $aspectMap = isset($options['aspect_map']) && is_array($options['aspect_map'])
        ? $options['aspect_map']
        : [
            'Manufacturer / Brand' => 'Brand',
            'Brand'                => 'Brand',
            'Manufacturer'         => 'Brand',
            'Face Diameter'        => 'Face Diameter',
            'Connection Diameter'  => 'Connection Size',
            'Mounting Position'    => 'Mounting Type',
            // add more mappings per category here…
        ];

    $query  = [];
    $extras = []; // repeated aspect_filter params appended manually

    // --- Category narrowing (AND) ---
    if (!empty($categoryId) || $categoryId === 0 || $categoryId === '0') {
        $query['category_ids'] = (string)$categoryId;
    }

    // --- q: keywords only (NO AND/OR/PARENS) ---
    $qTokens = [];
    if (trim($keywords) !== '') {
        $qTokens[] = trim($keywords);
    }

    // --- Filters ---
    $filters = is_array($filters) ? $filters : [];

    // Brand handling (recognized -> aspect_filter; unrecognized -> q tokens)
    $recognizedSet = [];
    foreach ($recognizedBrands as $rb) {
        $recognizedSet[mb_strtolower(trim($rb))] = true;
    }
    $brandKeyUsed = null;
    foreach (['Manufacturer / Brand','Brand','Manufacturer','Brand Name'] as $bk) {
        if (!empty($filters[$bk]) && is_array($filters[$bk])) { $brandKeyUsed = $bk; break; }
    }
    if ($brandKeyUsed !== null) {
        $recVals = [];
        $fallbackQ = [];
        foreach ($filters[$brandKeyUsed] as $v) {
            $clean = trim((string)$v);
            if ($clean === '') continue;
            $key = mb_strtolower($clean);
            if (isset($recognizedSet[$key])) {
                $recVals[$clean] = true; // dedupe
            } else {
                // fallback as plain token into q (implicit AND)
                $fallbackQ[$clean] = true;
            }
        }
        if (!empty($recVals)) {
            $extras[] = 'aspect_filter=' . rawurlencode('Brand:{' . implode('|', array_keys($recVals)) . '}');
        }
        if (!empty($fallbackQ)) {
            $qTokens[] = implode(' ', array_keys($fallbackQ));
        }
        unset($filters[$brandKeyUsed]);
    }

    // Other filters → aspects when mapped; else push tokens into q
    foreach ($filters as $uiName => $values) {
        if (!is_array($values) || empty($values)) continue;

        // Clean & dedupe & strip braces (conflict with { } syntax)
        $vals = [];
        foreach ($values as $v) {
            $v = str_replace(['{','}'], '', trim((string)$v));
            if ($v !== '') $vals[$v] = true;
        }
        if (empty($vals)) continue;

        if (isset($aspectMap[$uiName])) {
            $aspectName = $aspectMap[$uiName];
            // Repeat aspect_filter for each aspect; values are pipe-separated
            $extras[] = 'aspect_filter=' . rawurlencode($aspectName . ':{' . implode('|', array_keys($vals)) . '}');
        } else {
            // No mapping: add tokens to q (cannot express OR without multi-call fanout)
            $qTokens[] = implode(' ', array_keys($vals));
        }
    }

    if (!empty($qTokens)) {
        $query['q'] = trim(implode(' ', $qTokens));
    }

    // --- Global filter (price & condition) ---
    $filterClauses = [];
    $hasMin = isset($options['min_price']) && $options['min_price'] !== '' && is_numeric($options['min_price']);
    $hasMax = isset($options['max_price']) && $options['max_price'] !== '' && is_numeric($options['max_price']);
    if ($hasMin || $hasMax) {
        $min = $hasMin ? number_format((float)$options['min_price'], 2, '.', '') : '';
        $max = $hasMax ? number_format((float)$options['max_price'], 2, '.', '') : '';
        $filterClauses[] = 'price:[' . $min . '..' . $max . ']';
    }
    if (!empty($options['condition'])) {
        $c = mb_strtolower(trim((string)$options['condition']));
        if ($c === 'new')  $filterClauses[] = 'conditions:{NEW}';
        if ($c === 'used') $filterClauses[] = 'conditions:{USED}';
    }
    if (!empty($filterClauses)) {
        $query['filter'] = implode(',', $filterClauses);
    }

    // --- Sort / limit / offset (preserve your old tokens) ---
    if (!empty($options['sort'])) {
        switch ($options['sort']) {
            case 'price_asc':
            case 'price':    $query['sort'] = 'price';           break;
            case 'price_desc':
            case '-price':   $query['sort'] = 'priceDescending'; break;
        }
    }
    $query['limit']  = isset($options['limit'])  && (int)$options['limit']  > 0 ? (int)$options['limit']  : 50;
    $query['offset'] = isset($options['offset']) && (int)$options['offset'] >= 0 ? (int)$options['offset'] : 0;

    // --- Build URL: normal params + repeated aspect_filter params ---
    $url = $apiBase . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    if (!empty($extras)) {
        $url .= '&' . implode('&', $extras);
    }
    return $url;
}