<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/filter_helpers.php';

function construct_full_ebay_endpoint($params, $categoryId, $token) {
    $recognizedBrands = [];

    // Build aspect endpoint to retrieve brand list
    $brandUrl = construct_brand_list_endpoint($categoryId);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $brandUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]
    ]);
    $brandResponse = curl_exec($curl);
    curl_close($curl);

    $recognizedBrands = extract_brands_from_response($brandResponse);

    $q = isset($params['k']) ? $params['k'] : '';
    $unmatchedBrands = [];
    $matchedBrands = [];

    if (!empty($params['Manufacturer'])) {
        $manufacturers = is_array($params['Manufacturer']) ? $params['Manufacturer'] : [$params['Manufacturer']];
        foreach ($manufacturers as $manu) {
            if (in_array($manu, $recognizedBrands)) {
                $matchedBrands[] = $manu;
            } else {
                $unmatchedBrands[] = $manu;
            }
        }
    }

    $q .= ' ' . implode(' ', $unmatchedBrands);
    $endpoint = 'https://api.ebay.com/buy/browse/v1/item_summary/search?q=' . urlencode(trim($q));
    file_put_contents(__DIR__ . '/debug_ab.txt', "ENDPOINT (2): " . $endpoint);

    $filters = [];

    // Aspect filter
    if (!empty($matchedBrands)) {
        $escapedBrands = array_map('urlencode', $matchedBrands);
        $filters[] = 'aspect_filter=Brand:{' . implode(',', $escapedBrands) . '}';
    }

    // Condition
    if (!empty($params['Condition']) && $params['Condition'] !== 'Any') {
        $condId = $params['Condition'] === 'Used' ? '3000' : '1000';
        $filters[] = 'filter=conditionIds:' . $condId;
    }

    // Price range
    if (!empty($params['min_price']) || !empty($params['max_price'])) {
        $min = $params['min_price'] ?? '';
        $max = $params['max_price'] ?? '';
        if ($min !== '' || $max !== '') {
            $range = $min . '..' . $max;
            $filters[] = 'filter=price:[' . $range . ']';
        }
    }

    // Sort order
    $sort = 'price';
    if (!empty($params['sort_select'])) {
        $sort = ($params['sort_select'] === 'price_asc') ? 'price' : '-price';
    }

    // Pagination (default to 0)
    $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

    // Combine everything
    $endpoint .= '&category_ids=' . $categoryId;
    $endpoint .= '&' . implode('&', $filters);
    $endpoint .= '&sort=' . $sort . '&limit=50&offset=' . $offset;

    return $endpoint;
}
?>
