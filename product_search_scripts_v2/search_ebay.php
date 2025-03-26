<?php

header('Content-Type: application/json');

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/ebay_api_call_functions.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');

// 1. Parse incoming filter params
$params = get_search_parameters();

// 2. Build keyword string
$search_keyword_phrase = build_search_keyword_phrase($params);

// 3. Construct eBay API endpoint
$api_endpoint = construct_api_endpoint($search_keyword_phrase, $params);

// 4. Get auth token
$auth_token = get_ebay_oauth_token();

// 5. Fetch eBay data
$data = fetch_ebay_data($api_endpoint, $auth_token);

// 6. Return it
echo json_encode($data);
?>