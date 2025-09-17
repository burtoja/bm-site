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
 * Backward-compatible endpoint builder for eBay Browse API.
 * - Returns a STRING URL (what most callers expect).
 * - AND for category narrowing via category_ids.
 * - OR within a filter via aspect_filter: Aspect:{Val1|Val2|...}
 * - AND across different filters by repeating aspect_filter.
 * - q = keywords only (no AND/OR parens).
 *
 * If your app used a different function name (e.g., build_ebay_endpoint),
 * you can alias it to construct_search_endpoint at the bottom.
 */

function construct_final_ebay_endpoint(array $params): string {
    $apiBase = 'https://api.ebay.com/buy/browse/v1/item_summary/search?';

    // ---- 0) Mappings you can tweak per category
    // Recognized brands for the current eBay category (optional)
    $recognizedBrands = $params['recognized_brands'] ?? [];

    // Map your UI filter names -> eBay aspect names
    // Add or modify as needed for your categories.
    $aspectMap = $params['aspect_map'] ?? [
        'Manufacturer / Brand' => 'Brand',
        'Brand'                => 'Brand',
        'Face Diameter'        => 'Face Diameter',
        'Connection Diameter'  => 'Connection Size',
        'Mounting Position'    => 'Mounting Type',
        // ...extend per your domain...
    ];

    $query   = [];
    $extras  = []; // we’ll collect repeated aspect_filter params here and append manually

    // ---- 1) Category narrowing (deepest eBay cat)
    if (!empty($params['category_id'])) {
        $query['category_ids'] = (string)$params['category_id'];
    }

    // ---- 2) q = keywords only
    $qTokens = [];
    if (!empty($params['keywords'])) {
        $qTokens[] = trim($params['keywords']);
    }

    // ---- 3) Filters
    $filters = isset($params['filters']) && is_array($params['filters']) ? $params['filters'] : [];

    // (a) Brand handling: recognized -> aspect_filter Brand:{...}; others -> q tokens
    $recognizedSet = [];
    foreach ($recognizedBrands as $rb) {
        $recognizedSet[mb_strtolower(trim($rb))] = true;
    }
    $brandKeyUsed = null;
    foreach (['Manufacturer / Brand','Brand','Manufacturer','Brand Name'] as $bk) {
        if (!empty($filters[$bk]) && is_array($filters[$bk])) { $brandKeyUsed = $bk; break; }
    }
    if ($brandKeyUsed !== null) {
        $rec = [];
        $fallbackTokens = [];
        foreach ($filters[$brandKeyUsed] as $v) {
            $clean = trim($v);
            if ($clean === '') continue;
            $key = mb_strtolower($clean);
            if (isset($recognizedSet[$key])) {
                $rec[$clean] = true;
            } else {
                // push to q as plain tokens (no quotes/booleans)
                $fallbackTokens[$clean] = true;
            }
        }
        if (!empty($rec)) {
            $extras[] = 'aspect_filter=' . rawurlencode('Brand:{' . implode('|', array_keys($rec)) . '}');
        }
        if (!empty($fallbackTokens)) {
            $qTokens[] = implode(' ', array_keys($fallbackTokens));
        }
        unset($filters[$brandKeyUsed]);
    }

    // (b) Other filters -> aspects when mapped; else push tokens into q
    foreach ($filters as $uiName => $values) {
        if (!is_array($values) || empty($values)) continue;

        // Clean/dedupe
        $vals = [];
        foreach ($values as $v) {
            $v = trim($v);
            if ($v !== '') $vals[$v] = true;
        }
        if (empty($vals)) continue;

        if (isset($aspectMap[$uiName])) {
            $aspectName = $aspectMap[$uiName];

            // eBay accepts inch marks in aspect values; no quotes needed.
            // Just guard against braces which conflict with the syntax:
            $safeVals = array_map(function($x){
                return str_replace(['{','}'], '', $x);
            }, array_keys($vals));

            // Repeat aspect_filter per aspect (AND across aspects)
            $extras[] = 'aspect_filter=' . rawurlencode($aspectName . ':{' . implode('|', $safeVals) . '}');
        } else {
            // No mapping: these become additional tokens in q (implicit AND).
            $qTokens[] = implode(' ', array_keys($vals));
        }
    }

    if (!empty($qTokens)) {
        // Join with spaces; NO boolean operators.
        $query['q'] = trim(implode(' ', $qTokens));
    }

    // ---- 4) Global filter (price & condition)
    $filterClauses = [];
    $hasMin = isset($params['min_price']) && $params['min_price'] !== '' && is_numeric($params['min_price']);
    $hasMax = isset($params['max_price']) && $params['max_price'] !== '' && is_numeric($params['max_price']);
    if ($hasMin || $hasMax) {
        $min = $hasMin ? number_format((float)$params['min_price'], 2, '.', '') : '';
        $max = $hasMax ? number_format((float)$params['max_price'], 2, '.', '') : '';
        $filterClauses[] = 'price:[' . $min . '..' . $max . ']';
    }
    if (!empty($params['condition'])) {
        $c = mb_strtolower(trim($params['condition']));
        if ($c === 'new')  $filterClauses[] = 'conditions:{NEW}';
        if ($c === 'used') $filterClauses[] = 'conditions:{USED}';
    }
    if (!empty($filterClauses)) {
        $query['filter'] = implode(',', $filterClauses);
    }

    // ---- 5) Sort, limit, offset
    if (!empty($params['sort'])) {
        switch ($params['sort']) {
            case 'price_asc':
            case 'price':      $query['sort'] = 'price'; break;
            case 'price_desc':
            case '-price':     $query['sort'] = 'priceDescending'; break;
        }
    }
    $query['limit']  = isset($params['limit'])  && (int)$params['limit']  > 0 ? (int)$params['limit']  : 50;
    $query['offset'] = isset($params['offset']) && (int)$params['offset'] >= 0 ? (int)$params['offset'] : 0;

    // ---- 6) Build URL
    // Use http_build_query for normal params, then append repeated aspect_filter params.
    $base = $apiBase . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    if (!empty($extras)) {
        $base .= '&' . implode('&', $extras);
    }
    return $base;
}