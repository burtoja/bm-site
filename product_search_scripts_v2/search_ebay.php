<?php
/**
 * Executes eBay API call through a server-side proxy to avoid CORS.
 */
header('Content-Type: application/json');

require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/ebay_api_endpoint_construction.php';

$params = $_GET;
$categoryId = 12576; // Business & Industrial default

$token = getBasicOauthToken();

try {
    $endpoint = construct_full_ebay_endpoint($params, $categoryId, $token);
    file_put_contents(__DIR__ . '/debug_ab.txt', "ENDPOINT (1): " . $endpoint);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
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
    } elseif (!$response) {
        http_response_code(500);
        echo json_encode(["error" => "Empty response from eBay"]);
    } else {
        echo $response;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
