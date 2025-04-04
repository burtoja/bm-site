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
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/ebay_api_endpoint_construction.php';

$categoryId = 12576; // Hardcoded category: Business & Industrial
$params = $_GET; // Incoming search filters from frontend

// Validate incoming params
if (empty($params['k'])) {
    echo json_encode(["error" => "Missing required keyword 'k'."]);
    exit;
}

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

// Build final search URL
$searchEndpoint = construct_search_endpoint($params, $categoryId, $recognizedBrands);

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

// Output the final JSON response from eBay
echo $response;

// (Optional debug: write raw ebay api url and response)
// file_put_contents(__DIR__ . '/debug_search_endpoint.txt', $searchEndpoint);
// file_put_contents(__DIR__ . '/debug_response.json', $response);
