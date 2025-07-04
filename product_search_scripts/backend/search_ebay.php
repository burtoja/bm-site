<?php
// ==========================
// search_ebay.php
// ==========================
// Proxies search requests to eBay Browse API
//
//Summary:
//Sets the proper header.
//Loads the token and helper functions.
//Checks that k (keyword) is present.
//Fetches the recognized brands.
//Constructs the final eBay search endpoint cleanly.
//Makes the real search call.
//Returns the JSON result properly.


header('Content-Type: application/json');

require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/build_ebay_endpoint.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/common_search_functions.php';

//Turn on debugging log
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/debug_ebay_php_errors.log");
error_log("START: search_ebay is executing");

$categoryId = 12576; // Hardcoded category: Business & Industrial

// Check if 'q' is set — NOT 'k'
if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(["error" => "Missing required keyword 'q'."]);
    exit;
}

$q = $_GET['q'];

//Collect and condition sort parameter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'price';

// Collect other incoming params
$condition = isset($_GET['condition']) ? $_GET['condition'] : '';
$minPrice = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$maxPrice = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$aspectFilter = isset($_GET['aspect_filter']) ? $_GET['aspect_filter'] : '';
$subcategoryId = isset($_GET['subcategory_id']) ? intval($_GET['subcategory_id']) : null;

// Get OAuth token for eBay
$token = getBasicOauthToken();

// Fetch recognized brands for this category
$recognizedBrands = [];
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
$err = curl_error($curl);
curl_close($curl);

if (!$err && $brandResponse) {
    $recognizedBrands = extract_brands_from_response($brandResponse);
}

// --- Build aspect_filter from selected option IDs ---
$selectedOptions = [];
foreach ($_GET as $key => $value) {
    if (strpos($key, 'filter_') === 0 && is_array($value)) {
        $selectedOptions = array_merge($selectedOptions, $value);
    }
}

$aspectMap = get_aspect_filter_map_from_option_ids($selectedOptions);

// Build final search URL
$params = [
    'q' => $q,
    'sort' => $sort,
    'condition' => $condition,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'aspect_filter' => $aspectMap,
    'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0
];

$searchEndpoint = construct_final_ebay_endpoint($params, $recognizedBrands, $categoryId);
error_log("ENDPOINT (in search_ebay): " . $searchEndpoint);

// Fetch eBay search results
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $searchEndpoint,
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
    echo json_encode(["error" => "cURL error: $err"]);
    exit;
}

if (!$response) {
    echo json_encode(["error" => "Empty eBay response"]);
    exit;
}

// Decode, inject pagination metadata, and re-encode
$data = json_decode($response, true);

// Include offset so frontend knows what page we're on
$data['offset'] = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Forward total from eBay (if it's there), or set to 0
$data['total'] = isset($data['total']) ? (int)$data['total'] : 0;

// Debug output
error_log("eBay response total: " . print_r($data['total'], true));
error_log("Full decoded eBay response: " . print_r($data, true));

echo json_encode($data);