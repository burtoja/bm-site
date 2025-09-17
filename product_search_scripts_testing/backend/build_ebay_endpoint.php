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
 * Build eBay Browse API endpoint:
 * - NO boolean operators in q (keywords only)
 * - OR within a filter via aspect_filter: Aspect:{Val1|Val2|...}
 * - AND across different filters is natural (multiple aspect_filter aspects)
 * - Fallback: any filter that doesn’t map to an eBay aspect -> terms appended to q (implicit AND)
 *
 * @param array $params
 *   [
 *     'keywords'    => 'electrical motor',
 *     'category_id' => 12345,                      // eBay category id (deepest)
 *     'filters'     => [ 'Manufacturer / Brand' => ['WIKA','Ashcroft'], 'Face Diameter' => ['2.5"','4"'] ],
 *     'condition'   => 'New'|'Used',
 *     'min_price'   => 10.00,
 *     'max_price'   => 200.00,
 *     'sort'        => 'price_asc'|'price_desc'|...,
 *     'limit'       => 50,
 *     'offset'      => 0,
 *   ]
 * @param array $recognizedBrands   e.g., ['Ashcroft','WIKA','Dwyer']
 * @param array $aspectMap          Map your UI filter names -> eBay aspect names
 *                                  e.g., ['Manufacturer / Brand' => 'Brand',
 *                                         'Face Diameter'        => 'Gauge Face Diameter',
 *                                         'Connection Diameter'  => 'Connection Size']
 * @param bool  $debugReturnArray   If true, return ['url'=>..., 'query'=>...] for debugging.
 */
function construct_final_ebay_endpoint(
    array $params,
    array $recognizedBrands = [],
    array $aspectMap = [],
    bool $debugReturnArray = false
) {
    $apiBase = 'https://api.ebay.com/buy/browse/v1/item_summary/search?';
    $query   = [];

    // 1) Category narrowing (AND): supply the deepest eBay category id
    if (!empty($params['category_id'])) {
        $query['category_ids'] = (string)$params['category_id'];
    }

    // 2) Prepare q: keywords only (NO AND/OR/PARENS)
    $qTokens = [];
    if (!empty($params['keywords'])) {
        $qTokens[] = trim($params['keywords']);
    }

    $filters = isset($params['filters']) && is_array($params['filters']) ? $params['filters'] : [];

    // 3) Brand: recognized go to aspect_filter; unrecognized go to q as plain tokens
    $recognizedSet = [];
    foreach ($recognizedBrands as $rb) {
        $recognizedSet[mb_strtolower(trim($rb))] = true;
    }
    $brandKeys = ['Manufacturer', 'Manufacturer / Brand', 'Brand', 'Brand Name'];
    $brandKeyUsed = null;
    foreach ($brandKeys as $bk) {
        if (!empty($filters[$bk]) && is_array($filters[$bk])) { $brandKeyUsed = $bk; break; }
    }

    $aspectFilters = []; // collect strings like "Brand:{Ashcroft|WIKA}"
    if ($brandKeyUsed !== null) {
        $recVals = [];
        $unrecQ  = [];
        foreach ($filters[$brandKeyUsed] as $v) {
            $clean = trim($v);
            if ($clean === '') { continue; }
            $key = mb_strtolower($clean);
            if (isset($recognizedSet[$key])) {
                $recVals[$clean] = true; // dedupe
            } else {
                // fallback: add to q as plain term (quoted to keep phrase together)
                $unrecQ["\"{$clean}\""] = true;
            }
        }
        if (!empty($recVals)) {
            $aspectFilters[] = 'Brand:{' . implode('|', array_keys($recVals)) . '}';
        }
        if (!empty($unrecQ)) {
            $qTokens[] = implode(' ', array_keys($unrecQ)); // no AND/OR, just tokens
        }
        unset($filters[$brandKeyUsed]);
    }

    // 4) Map remaining filters to aspects when possible; otherwise push values into q (tokens)
    foreach ($filters as $uiName => $values) {
        if (!is_array($values) || empty($values)) { continue; }
        $aspectName = $aspectMap[$uiName] ?? null;

        // Clean and dedupe
        $vals = [];
        foreach ($values as $v) {
            $v = trim($v);
            if ($v !== '') { $vals[$v] = true; }
        }
        if (empty($vals)) { continue; }

        if ($aspectName) {
            // eBay OR within this aspect via pipes
            $aspectFilters[] = $aspectName . ':{' . implode('|', array_keys($vals)) . '}';
        } else {
            // No aspect mapping: put them into q as plain quoted tokens (implicit AND)
            // NOTE: This cannot express OR in a single call. If you *must* OR these, you’ll need
            // to fan out multiple API calls and merge results client-side.
            $quoted = array_map(fn($x) => "\"{$x}\"", array_keys($vals));
            $qTokens[] = implode(' ', $quoted);
        }
    }

    // 5) Finalize q
    if (!empty($qTokens)) {
        // Join with spaces. No boolean operators.
        $query['q'] = trim(implode(' ', $qTokens));
    }

    // 6) aspect_filter
    if (!empty($aspectFilters)) {
        // Multiple aspects separated by commas are fine
        $query['aspect_filter'] = implode(',', $aspectFilters);
    }

    // 7) Global filter (price & condition)
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
        if ($c === 'new')  { $filterClauses[] = 'conditions:{NEW}'; }
        if ($c === 'used') { $filterClauses[] = 'conditions:{USED}'; }
    }
    if (!empty($filterClauses)) {
        $query['filter'] = implode(',', $filterClauses);
    }

    // 8) Sort
    if (!empty($params['sort'])) {
        switch ($params['sort']) {
            case 'price_asc':  $query['sort'] = 'price';             break;
            case 'price_desc': $query['sort'] = 'priceDescending';   break;
            // Optionally support your legacy UI tokens
            case '-price':     $query['sort'] = 'priceDescending';   break;
            case 'price':      $query['sort'] = 'price';             break;
            // Add more mappings as needed
        }
    }

    // 9) Pagination
    $query['limit']  = isset($params['limit'])  && (int)$params['limit']  > 0 ? (int)$params['limit']  : 50;
    $query['offset'] = isset($params['offset']) && (int)$params['offset'] >= 0 ? (int)$params['offset'] : 0;

    // 10) Build URL
    $url = $apiBase . http_build_query($query);

    if ($debugReturnArray) {
        return ['url' => $url, 'query' => $query];
    }
    return $url;
}