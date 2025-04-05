<?php
/**
 * ebay_api_endpoint_construction.php
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

    $data = json_decode($response, true);
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
 * Builds the final eBay search endpoint based on parameters and recognized brands.
 *
 * @param array $params Flat array of filters (k, manufacturer, condition, min_price, etc)
 * @param array $recognizedBrands List of brands available in the category
 * @param int $categoryId
 * @return string
 */
function construct_final_ebay_endpoint(array $params, array $recognizedBrands, int $categoryId) {
    $query = [];

    // Always set the base keyword (k becomes q)
    $q = isset($params['q']) ? trim($params['q']) : '';
    unset($params['q']);
    if (empty($q)) {
        error_log("Missing required keyword 'q'.");
        return null;
    }



    // Handle condition filter
    if (!empty($params['condition'])) {
        $cond = strtolower($params['condition']);
        if ($cond === 'used') {
            $query['filter'][] = 'conditionIds:3000';
        } elseif ($cond === 'new') {
            $query['filter'][] = 'conditionIds:1000';
        }
    }

    // Handle price range
    if (!empty($params['min_price']) || !empty($params['max_price'])) {
        $min = $params['min_price'] ?? '';
        $max = $params['max_price'] ?? '';
        $query['filter'][] = "price:[{$min}..{$max}]";
    }

    //$brandList = get_available_brands_in_category($categoryId);

    // Handle manufacturer / brand logic
    if (!empty($params['manufacturer'])) {
        $manufacturer = trim($params['manufacturer']);
        if (in_array($manufacturer, $recognizedBrands)) {
            $query['aspect_filter'] = "Brand:{{$manufacturer}}";
        } else {
            $q .= ' ' . $manufacturer;
        }
    }

    // Handle sort order
    if (!empty($params['sort_select'])) {
        $sort = strtolower($params['sort_select']) === 'price_asc' ? 'price' : '-price';
        $query['sort'] = $sort;
    }

    // Default limit and offset
    $query['limit'] = 50;
    $query['offset'] = 0;

    // Final assembly
    $endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . urlencode($q);
    $endpoint .= "&category_ids=$categoryId";
    $queryStringParts = [];
    foreach ($query as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $queryStringParts[] = urlencode($key) . '=' . urlencode($v);
            }
        } else {
            $queryStringParts[] = urlencode($key) . '=' . urlencode($value);
        }
    }

    //$finalUrl = $endpoint . '?' . implode('&', $queryStringParts);
    $finalUrl = $endpoint . '?' . http_build_query($queryStringParts, '', '&', PHP_QUERY_RFC3986);
    error_log("FINAL URL: " . $finalUrl);
    return $finalUrl;
}
