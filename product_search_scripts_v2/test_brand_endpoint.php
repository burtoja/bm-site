<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/common_search_functions.php';
 echo "1";
$token = getBasicOauthToken();
echo "2";
$categoryId = 12576; // Business & Industrial
$url = construct_brand_list_endpoint($categoryId);
echo "3";

// Diagnostic log
echo "<strong>Endpoint URL:</strong> $url<br>";
echo "<strong>Token (start):</strong> " . substr($token, 0, 30) . "...<br><br>";

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
    echo "❌ CURL ERROR: $err";
} elseif (!$response) {
    echo "❌ Empty response.";
} else {
    echo "✅ Raw response preview:<br><pre>" . htmlspecialchars(substr($response, 0, 2000)) . "</pre>";
}
