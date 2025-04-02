<?php
/**
 * Executes eBay API call through a server-side proxy to avoid CORS
 */
header('Content-Type: application/json');

require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/ebay_api_endpoint_construction.php';

// Debug mode (toggle manually or based on param)
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

// Extract query params
$params = $_GET;

// Translate internal 'k' to eBay's 'q'
if (isset($params['k']) && !isset($params['q'])) {
    $params['q'] = $params['k'];
    unset($params['k']);
}

// Build the base query string
$ebayParams = [];

// Base keyword search
if (!empty($params['q'])) {
    $ebayParams[] = 'q=' . urlencode($params['q']);
}

// Add aspect filter for manufacturer brand matching (already handled upstream)
if (!empty($params['aspect_filter'])) {
    $ebayParams[] = 'aspect_filter=' . urlencode($params['aspect_filter']);
}

// Add sort
if (!empty($params['sort_select'])) {
    $sort = ($params['sort_select'] === 'price_asc') ? '-price' : 'price';
    $ebayParams[] = 'sort=' . $sort;
}

// Filters we'll skip from building dynamic filters
$skipKeys = ['q', 'sort_select', 'condition', 'custom_price_min', 'custom_price_max', 'aspect_filter'];

// Start building eBay filters
$filters = [];

// General filters from query string (Manufacturer, Configuration, Type, etc.)
foreach ($params as $key => $value) {
    if (in_array($key, $skipKeys)) continue;

    $values = is_array($value) ? $value : [$value];
    foreach ($values as $v) {
        if (trim($v) !== '') {
            $normalizedKey = strtolower(str_replace(' ', '_', $key));
            $filters[] = "{$normalizedKey}:{" . addslashes($v) . "}";
        }
    }
}

// Condition
if (!empty($params['Condition']) && $params['Condition'] !== 'Any') {
    $conditionId = ($params['Condition'] === 'Used') ? '3000' : '1000';
    $filters[] = "conditionIds:$conditionId";
}

// Price Range
$min = $params['custom_price_min'] ?? '';
$max = $params['custom_price_max'] ?? '';
if ($min !== '' || $max !== '') {
    $range = $min . '..' . $max;
    $filters[] = "price:[$range]";
}

// Combine filters into one `filter` param
if (!empty($filters)) {
    $ebayParams[] = 'filter=' . urlencode(implode(',', $filters));
}

// Final API URL
$url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?' . implode('&', $ebayParams);

// Optional debug output for browser (only while testing)
if ($debug) {
    echo json_encode([
        'final_url' => $url,
        'raw_params' => $params,
        'parsed_filters' => $filters
    ]);
    exit;
}

// Retrieve OAuth token
$token = getBasicOauthToken();

// cURL request to eBay
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

// Handle response
if ($err) {
    http_response_code(500);
    echo json_encode(["error" => "cURL error: $err"]);
    exit;
}

if (!$response || empty($response)) {
    http_response_code(500);
    echo json_encode(["error" => "Empty response from eBay"]);
    exit;
}

echo $response;
