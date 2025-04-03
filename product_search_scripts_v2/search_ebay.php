<?php
/**
 * Executes eBay API call through a server-side proxy to avoid CORS
 *
 * Example of properly formatted endpoint:
 * https://api.ebay.com/buy/browse/v1/item_summary/search?q=Bearings+Ajanta&category_ids=12576&aspect_filter=Brand:{Ajanta}&filter=conditionIds:1000,price:[100..500]&sort=price&limit=50&offset=0
 *
 */
header('Content-Type: application/json');
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/ebay_api_endpoint_construction.php';

// Hardcoded category ID for now
$categoryId = 12576;

// Get parameters from URL
$params = $_GET;

// Translate 'k' to 'q' if needed
if (isset($params['k']) && !isset($params['q'])) {
    $params['q'] = $params['k'];
    unset($params['k']);
}

$keyword = isset($params['q']) ? trim($params['q']) : '';
$manufacturerRaw = $params['Manufacturer'] ?? '';
$manufacturers = is_array($manufacturerRaw) ? $manufacturerRaw : explode(',', $manufacturerRaw);

// Get token and brand list
$token = getBasicOauthToken();
$brandEndpoint = construct_brand_list_endpoint($categoryId);
$brandResponse = fetch_ebay_api($brandEndpoint, $token);
$recognizedBrands = extract_brands_from_response($brandResponse);

// Divide into matched/unmatched brands
$matchedBrands = [];
$unmatchedBrands = [];
foreach ($manufacturers as $manu) {
    if (in_array($manu, $recognizedBrands)) {
        $matchedBrands[] = $manu;
    } else {
        $unmatchedBrands[] = $manu;
    }
}

// Start building endpoint
$endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?";
$query = $keyword;
if (!empty($unmatchedBrands)) {
    $query .= ' ' . implode(' ', $unmatchedBrands);
}
$endpoint .= "q=" . urlencode(trim($query));

// Add category
$endpoint .= "&category_ids=$categoryId";

// Add aspect filter for brands
if (!empty($matchedBrands)) {
    $escaped = array_map('urlencode', $matchedBrands);
    $endpoint .= "&aspect_filter=" . urlencode("categoryId:$categoryId,Brand:{" . implode(',', $escaped) . "}");
}

// Condition
if (!empty($params['Condition']) && $params['Condition'] !== 'Any') {
    $condId = $params['Condition'] === 'Used' ? '3000' : '1000';
    $endpoint .= "&filter=conditionIds:$condId";
}

// Custom price range
if (!empty($params['custom_price_min']) || !empty($params['custom_price_max'])) {
    $min = $params['custom_price_min'] ?? '';
    $max = $params['custom_price_max'] ?? '';
    if ($min !== '' || $max !== '') {
        $range = $min . '..' . $max;
        $endpoint .= "&filter=price:[" . $range . "]";
    }
}

// Sorting
if (!empty($params['Sort Order'])) {
    $sortOrder = strtolower($params['Sort Order']) === 'low to high' ? '-price' : 'price';
    $endpoint .= "&sort=" . $sortOrder;
}

// Paging
$endpoint .= "&limit=50&offset=0";

// Debug: Write URL and brand list
file_put_contents(__DIR__ . '/debug_last_url.txt', $endpoint);
file_put_contents(__DIR__ . '/debug_brands.txt', print_r($recognizedBrands, true));

// Final API Call
$response = fetch_ebay_api($endpoint, $token);
if (!$response) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to retrieve eBay data."]);
    exit;
}

echo $response;

/**
 * Helper function to call eBay API with headers
 */
function fetch_ebay_api($url, $token) {
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
    curl_close($curl);
    return $response;
}
