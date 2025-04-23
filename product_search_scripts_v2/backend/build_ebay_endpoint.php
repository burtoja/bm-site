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
    $api_base = "https://api.ebay.com/buy/browse/v1/item_summary/search?";
    $query = [];

    // Always set the base keyword (k becomes q in another place)
    if (!isset($params['q'])) {
        throw new Exception("Missing required keyword 'q'.");
    }
    $query['q'] = $params['q'];

    // If misc_filters exist, append them
    if (!empty($params['misc_filters']) && is_array($params['misc_filters'])) {
        $miscKeywords = array_map('trim', $params['misc_filters']);
        $query['q'] .= ' ' . implode(' ', $miscKeywords);
    }

    // Always add category
    $query['category_ids'] = $categoryId;

    // Handle manufacturer / brand logic
    //$brandList = get_available_brands_in_category($categoryId);
    if (!empty($params['manufacturer'])) {
        $manufacturer = trim($params['manufacturer']);
        if (in_array($manufacturer, $recognizedBrands)) {
            $query['aspect_filter'] = "Brand:{{$manufacturer}}";
        } else {
            $query['q'] .= ' ' . $manufacturer;
        }
    }

    // Handle filters separately
    $filters = [];

    // Handle condition filter
    if (!empty($params['condition'])) {
        $cond = strtolower($params['condition']);
        if ($cond === 'used') {
            $filters[] = 'conditionIds:{3000}';
        } elseif ($cond === 'new') {
            $filters[] = 'conditionIds:{1000}';
        }
    }

    // Handle price range filter --> price_range == Any  OR price_range == Under_100  OR price_range == Custom
    if (!empty($params['price_range']) && $params['price_range'] !== 'Any' && $params['price_range'] !== 'Custom') {
        if ($params['price_range'] === 'Under_100') {
            $filters[] = "price%3A%5B..100%5D" . ",priceCurrency:USD";   // price:[..100] (colon and brackets encoded)
        }
    } elseif (!empty($params['custom_price_range_min']) || !empty($params['custom_price_range_max'])) {
        // Handle custom price inputs
        $min = $params['custom_price_range_min'] ?? '';
        $max = $params['custom_price_range_max'] ?? '';
        $filters[] = "price%3A%5B{$min}..{$max}%5D" . ",priceCurrency:USD";   // (colon and brackets encoded)
    }

    //Add filters to the query array
    if (!empty($filters)) {
        $query['filter'] = implode(',', $filters);
    }

    // Sorting
    if (!empty($params['sort_order'])) {
        $query['sort'] = ($params['sort_order'] === 'Low to High') ? 'price' : '-price';
    } else {
        $query['sort'] = '-price'; // default
    }

    if (!empty($params['sort'])) {
        $query[] = "sort=" . urlencode($params['sort']);
    }

    // Default result limit and offset
    $query['limit'] = 50;


    // Grab offset or set it to zero if none is present
    $query['offset']  = isset($params['offset']) ? (int)$params['offset'] : 0;

    // Build final query
    $final_url = $api_base . http_build_query($query);
    error_log("Final constructed endpoint: " . $final_url);

    return $final_url;
}
