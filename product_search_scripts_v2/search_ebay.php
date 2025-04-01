<?php
header('Content-Type: application/json');
require_once $_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/ebay_api_endpoint_construction.php';

// Debug flag (set to true while testing)
$debug = true;

$params = $_GET;

// STEP 1: Extract keyword and other known filters
$keyword     = isset($params['k']) ? $params['k'] : '';
$condition   = isset($params['condition']) ? $params['condition'] : ''; // "Used" or "New"
$manufacturer = isset($params['manufacturer']) ? $params['manufacturer'] : '';
$type        = isset($params['type']) ? $params['type'] : '';
$sort        = isset($params['sort_select']) && $params['sort_select'] === 'price_asc' ? '-price' : 'price';
$minPrice    = isset($params['custom_price_min']) ? $params['custom_price_min'] : '';
$maxPrice    = isset($params['custom_price_max']) ? $params['custom_price_max'] : '';

$filters = [];

// STEP 2: Add condition filter (if present)
if ($condition === 'Used') {
    $filters[] = 'conditionIds:3000';
} elseif ($condition === 'New') {
    $filters[] = 'conditionIds:1000';
}

// STEP 3: Add price range
if ($minPrice !== '' || $maxPrice !== '') {
    $range = ($minPrice !== '' ? $minPrice : '') . '..' . ($maxPrice !== '' ? $maxPrice : '');
    $filters[] = 'price:[' . $range . ']';
}

// STEP 4: Add type filter (if provided)
if (!empty($type)) {
    $filters[] = 'type:{"' . addslashes($type) . '"}';
}

// STEP 5: Add additional filters dynamically (skip known ones)
$skipKeys = ['k', 'q', 'condition', 'manufacturer', 'sort_select', 'custom_price_min', 'custom_price_max'];

foreach ($params as $key => $value) {
    if (in_array(strtolower($key), $skipKeys)) continue;

    if (!is_array($value)) {
        $value = [$value];
    }

    foreach ($value as $v) {
        if (trim($v) !== '') {
            // Normalize key name for eBay filter (spaces to underscores, lowercase)
            $normalizedKey = strtolower(str_replace(' ', '_', $key));
            $filters[] = "{$normalizedKey}:{" . addslashes($v) . "}";
        }
    }
}

// STEP 6: Build the final eBay API endpoint
$ebayBase = 'https://api.ebay.com/buy/browse/v1/item_summary/search';

$q = trim($keyword . ' ' . $manufacturer); // Both contribute to q
$queryParts = [
    'q=' . urlencode($q),
    'limit=50',
    'offset=0',
    'sort=' . $sort
];

if (!empty($filters)) {
    $queryParts[] = 'filter=' . urlencode(implode(',', $filters));
}

$finalUrl = $ebayBase . '?' . implode('&', $queryParts);

// STEP 7: Add token and send the request
$token = getBasicOauthToken();

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $finalUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// Debug mode
if ($debug) {
    echo json_encode([
        'debug_final_url' => $finalUrl,
        'filters_used' => $filters,
        'raw_response' => json_decode($response, true),
        'error' => $err
    ]);
    exit;
}

// Standard output
if ($err) {
    http_response_code(500);
    echo json_encode(["error" => "cURL error: $err"]);
} else {
    echo $response;
}
?>
