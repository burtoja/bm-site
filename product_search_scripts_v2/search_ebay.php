<?php
/**
 * Executes eBay API call through a server-side proxy to avoid CORS
 */
header('Content-Type: application/json');

require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/common_search_functions.php';

$params = $_GET;

// Translate internal 'k' keyword to eBay's 'q'
if (isset($params['k']) && !isset($params['q'])) {
    $params['q'] = $params['k'];
    unset($params['k']);
}

// Extract selected manufacturers
$selectedManufacturers = [];
if (!empty($params['Manufacturer'])) {
    if (is_array($params['Manufacturer'])) {
        $selectedManufacturers = $params['Manufacturer'];
    } else {
        $selectedManufacturers = explode(',', $params['Manufacturer']);
    }
}

// Get category ID (required for brand matching)
$categoryId = 12576; //Business & Industrial
$recognizedBrands = [];

if (!empty($selectedManufacturers) && $categoryId) {
    $token = getBasicOauthToken();
    $brandEndpoint = construct_brand_list_endpoint($categoryId);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $brandEndpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]
    ]);
    $brandResponse = curl_exec($curl);
    curl_close($curl);

    $recognizedBrands = extract_brands_from_response($brandResponse);
}

// Split manufacturers into matched vs unmatched
$matchedBrands = [];
$unmatchedBrands = [];

foreach ($selectedManufacturers as $manu) {
    if (in_array($manu, $recognizedBrands)) {
        $matchedBrands[] = $manu;
    } else {
        $unmatchedBrands[] = $manu;
    }
}

// Build final eBay search parameters
$ebayParams = [];

// q keyword
if (!empty($params['q'])) {
    $ebayParams[] = 'q=' . urlencode(trim($params['q'] . ' ' . implode(' ', $unmatchedBrands)));
}

// aspect_filter
if (!empty($matchedBrands)) {
    $escapedBrands = array_map('urlencode', $matchedBrands);
    $ebayParams[] = 'aspect_filter=Brand:{' . implode(',', $escapedBrands) . '}';
}

// sort order
if (!empty($params['Sort Order'])) {
    $sort = strtolower($params['Sort Order']) === 'low to high' ? '-price' : 'price';
    $ebayParams[] = 'sort=' . $sort;
}

// condition
if (!empty($params['Condition']) && $params['Condition'] !== 'Any') {
    $conditionId = $params['Condition'] === 'Used' ? '3000' : '1000';
    $ebayParams[] = 'filter=conditionIds:' . $conditionId;
}

// price range
if (!empty($params['custom_price_min']) || !empty($params['custom_price_max'])) {
    $min = $params['custom_price_min'] ?? '';
    $max = $params['custom_price_max'] ?? '';
    if ($min !== '' || $max !== '') {
        $range = $min . '..' . $max;
        $ebayParams[] = 'filter=price:[' . $range . ']';
    }
}

// final URL
$url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?' . implode('&', $ebayParams);
error_log("eBay API URL: " . $url);

// send the request
$token = $token ?? getBasicOauthToken(); // reuse if already fetched

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    http_response_code(500);
    echo json_encode(["error" => "cURL error: $err"]);
    exit;
}

if ($response === false || empty($response)) {
    http_response_code(500);
    echo json_encode(["error" => "Empty or invalid response from eBay."]);
    exit;
}

// Finally output the eBay response
echo $response;

