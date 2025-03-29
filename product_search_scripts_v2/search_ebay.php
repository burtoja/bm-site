<?php
/**
 * Executes ebay api call
 */
header('Content-Type: application/json');
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';

$query = $_GET['q'] ?? '';
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

