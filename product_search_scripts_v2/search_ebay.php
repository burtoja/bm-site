<?php
/**
 * Executes ebay api call
 */

header('Content-Type: application/json');
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';

$params = $_GET;

// Translate 'k' to eBay's 'q'
if (isset($params['k']) && !isset($params['q'])) {
    $params['q'] = $params['k'];
    unset($params['k']);
}

// Start query params array
$ebayParams = [];

// Required: q
if (!empty($params['q'])) {
    $ebayParams[] = 'q=' . urlencode($params['q']);
}

// Optional: sort
if (!empty($params['Sort Order'])) {
    $sort = strtolower($params['Sort Order']) === 'low to high' ? '-price' : 'price';
    $ebayParams[] = 'sort=' . $sort;
}

// Optional: condition filter
if (!empty($params['Condition']) && $params['Condition'] !== 'Any') {
    $conditionId = $params['Condition'] === 'Used' ? '3000' : '1000'; // eBay codes
    $ebayParams[] = 'filter=conditionIds:' . $conditionId;
}

// Optional: custom price range
if (!empty($params['custom_price_min']) || !empty($params['custom_price_max'])) {
    $min = $params['custom_price_min'] ?? '';
    $max = $params['custom_price_max'] ?? '';
    if ($min !== '' || $max !== '') {
        $range = $min . '..' . $max;
        $ebayParams[] = 'filter=price:[' . $range . ']';
    }
}

// Final URL
$url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?' . implode('&', $ebayParams);

$token = getBasicOauthToken();

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
