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
 * Build an eBay Browse API endpoint that uses:
 *  - AND logic for category/subcategory/sub-subcategory (via category_ids)
 *  - OR logic within each filter group in the q= string
 *  - AND logic across different filters
 * Also moves recognized brands to aspect_filter and keeps unrecognized in q.
 *
 * Expected $params shape (examples):
 * [
 *   'keywords'        => 'gauge pressure',          // optional free-text keywords
 *   'category_id'     => 12345,                     // deepest/eBay category id
 *   'filters'         => [                          // filter name => array of option strings
 *       'Manufacturer / Brand' => ['Ashcroft', 'WIKA'],
 *       'Connection Diameter'  => ['1/4"', '1/2"'],
 *       'Face Diameter'        => ['2.5"', '4"']
 *   ],
 *   'condition'       => 'New' | 'Used',            // optional
 *   'min_price'       => 25.00,                     // optional
 *   'max_price'       => 200.00,                    // optional
 *   'sort'            => 'price_asc'|'price_desc',  // optional
 *   'limit'           => 50,                        // optional (default 50)
 *   'offset'          => 0                          // optional (default 0)
 * ]
 *
 * $recognizedBrands: array of strings for the eBay category (from your brand lookup)
 */

function construct_final_ebay_endpoint(array $params, array $recognizedBrands = [])
{
    $apiBase = 'https://api.ebay.com/buy/browse/v1/item_summary/search?';
    $query = [];

    // 1) Category: pass the *deepest* eBay category id
    $categoryId = isset($params['category_id']) ? (string)$params['category_id'] : '';
    if ($categoryId !== '') {
        $query['category_ids'] = $categoryId;
    }

    // 2) Build keyword logic (q param)
    //    - Start with any free text keywords
    //    - For each filter: (opt1 OR opt2 OR ...)  // parentheses, OR within
    //    - AND these groups together
    $qParts = [];

    if (!empty($params['keywords'])) {
        $qParts[] = trim($params['keywords']);
    }

    $filters = isset($params['filters']) && is_array($params['filters']) ? $params['filters'] : [];

    // Handle Brand special-case: recognized brands go to aspect_filter, unrecognized stay in q
    $brandGroupForQ = [];      // unrecognized brand tokens kept in q
    $brandAspectSet = [];      // recognized brand tokens moved to aspect_filter

    // Normalize recognized brand set for quick lookup
    $recognizedSet = [];
    foreach ($recognizedBrands as $rb) {
        $recognizedSet[mb_strtolower(trim($rb))] = true;
    }

    // Separate out brand/manufacturer if present
    $brandKeys = ['Manufacturer', 'Manufacturer / Brand', 'Brand', 'Brand Name'];
    $brandKeyUsed = null;
    foreach ($brandKeys as $bk) {
        if (isset($filters[$bk]) && is_array($filters[$bk]) && count($filters[$bk]) > 0) {
            $brandKeyUsed = $bk;
            break;
        }
    }

    if ($brandKeyUsed !== null) {
        foreach ($filters[$brandKeyUsed] as $val) {
            $clean = trim($val);
            $key = mb_strtolower($clean);
            if (isset($recognizedSet[$key])) {
                $brandAspectSet[$clean] = true; // use set to deduplicate
            } else {
                $brandGroupForQ[] = "\"{$clean}\"";
            }
        }
        // Remove this brand filter from the general filter handling (already processed)
        unset($filters[$brandKeyUsed]);
    }

    // Process remaining filters into OR-groups for q
    foreach ($filters as $filterName => $values) {
        if (!is_array($values) || count($values) === 0) {
            continue;
        }
        // QUOTE each value and OR them inside a group
        $vals = [];
        foreach ($values as $v) {
            $v = trim($v);
            if ($v === '') {
                continue;
            }
            // quote to keep multiword phrases together
            $vals[] = "\"{$v}\"";
        }
        if (count($vals) > 0) {
            $qParts[] = '(' . implode(' OR ', $vals) . ')';
        }
    }

    // If unrecognized brands, include them as another OR-group in q
    if (count($brandGroupForQ) > 0) {
        $qParts[] = '(' . implode(' OR ', $brandGroupForQ) . ')';
    }

    // Join all groups with AND (implicit AND also works, but make it explicit for clarity)
    // Example: (filter1a OR filter1b) AND (filter2a OR filter2b) AND keywords...
    $query['q'] = count($qParts) > 0 ? implode(' AND ', $qParts) : '';

    // 3) aspect_filter for recognized brands
    // eBay expects: aspect_filter=Brand:{Dell|HP|Lenovo}
    // If need multiple aspect_filters, can repeat the parameter; here we concatenate.
    $aspectFilters = [];
    if (count($brandAspectSet) > 0) {
        $brandList = implode('|', array_keys($brandAspectSet));
        // Escape curly braces in values if any (rare)
        $brandList = str_replace(['{', '}'], ['', ''], $brandList);
        $aspectFilters[] = "Brand:{{$brandList}}";
    }
    if (count($aspectFilters) > 0) {
        // Note: You can repeat 'aspect_filter' or combine into one separated by commas.
        // eBay Browse API accepts multiple aspect_filter params. We'll combine with commas:
        $query['aspect_filter'] = implode(',', $aspectFilters);
    }

    // 4) Global filters (price, condition)
    // eBay Browse API 'filter' param accepts CSV of constraints, e.g.:
    //   filter=price:[10..100],conditions:{NEW|USED}
    $filterClauses = [];

    // Price range
    $hasMin = isset($params['min_price']) && $params['min_price'] !== '' && is_numeric($params['min_price']);
    $hasMax = isset($params['max_price']) && $params['max_price'] !== '' && is_numeric($params['max_price']);
    if ($hasMin || $hasMax) {
        $min = $hasMin ? number_format((float)$params['min_price'], 2, '.', '') : '';
        $max = $hasMax ? number_format((float)$params['max_price'], 2, '.', '') : '';
        // Construct [min..max] syntax (empty ends are allowed as open intervals)
        $range = '[' . ($min !== '' ? $min : '') . '..' . ($max !== '' ? $max : '') . ']';
        $filterClauses[] = "price:{$range}";
    }

    // Condition
    if (!empty($params['condition'])) {
        $cond = mb_strtolower(trim($params['condition']));
        // Map friendly -> eBay token
        $condToken = null;
        if ($cond === 'new') {
            $condToken = 'NEW';
        }
        if ($cond === 'used') {
            $condToken = 'USED';
        }
        if ($condToken) {
            $filterClauses[] = "conditions:{{$condToken}}";
        }
    }

    if (count($filterClauses) > 0) {
        // Comma separated list
        $query['filter'] = implode(',', $filterClauses);
    }

    // 5) Sort
    // Common: price, priceDescending, pricePlusShipping, pricePlusShippingHighest
    if (!empty($params['sort'])) {
        if ($params['sort'] === 'price_asc') {
            $query['sort'] = 'price'; // ascending
        } elseif ($params['sort'] === 'price_desc') {
            $query['sort'] = 'priceDescending';
        }
        // Add other mappings as desired
    }

    // 6) Pagination
    $query['limit'] = isset($params['limit']) && (int)$params['limit'] > 0 ? (int)$params['limit'] : 50;
    $query['offset'] = isset($params['offset']) && (int)$params['offset'] >= 0 ? (int)$params['offset'] : 0;

    // 7) Build final URL
    // Ensure stable http_build_query behavior for commas and braces
    // (eBay handles URL-encoded characters fine)
    $finalUrl = $apiBase . http_build_query($query);
    return $finalUrl;
}