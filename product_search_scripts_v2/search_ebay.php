<?php
/**
 * Executes ebay api call
 */
header('Content-Type: application/json');
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';

$query = $_GET['q'] ?? '';

$ebayParams = [];

if (!empty($_GET['q'])) {
    $ebayParams[] = 'q=' . urlencode($_GET['q']);
}
if (!empty($_GET['sort_order'])) {
    $ebayParams[] = 'sort=' . ($_GET['sort_order'] === 'price_desc' ? 'price' : '-price');
}
if (!empty($_GET['condition'])) {
    $ebayParams[] = 'filter=conditionIds:' . ($_GET['condition'] === 'Used' ? '3000' : '1000'); // eBay condition IDs
}

// Add more filters as needed (e.g., manufacturer as keyword filters, etc.)

$url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?' . implode('&', $ebayParams);

if (!$query) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing query']);
    exit;
}

$token = getBasicOauthToken();
$url = "https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . urlencode($query);

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
} else {
    echo $response;
}

