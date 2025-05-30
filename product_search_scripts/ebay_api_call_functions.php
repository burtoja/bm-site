<?php
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');

/**
 * Gets the eBay OAuth token
 *
 * @return  token
 **/
function get_ebay_oauth_token() {
    include ($_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php');
    return getBasicOauthToken();
}

/**
 * Builds the search phrase to be used in the API call
 *
 * @return  string with the conditioned keyword phrase
 **/
function build_search_keyword_phrase($params) {
    $phrase = $params['k'];
    if (!empty($params['search_keyword_phrase'])) {
        $phrase .= "+" . $params['search_keyword_phrase'];
    }
    //MOVED manufacturer to search only in brand field
    //    if (!empty($params['manufacturer'])) {
    //        $phrase .= "+" . $params['manufacturer'];
    //    }
    if (!empty($params['type'])) {
        $phrase .= "+" . $params['type'];
    }
    return str_replace('%2B', '+', rawurlencode($phrase));
}

/**
 * Constructs the API endpoint
 * i.e.
 * https://api.ebay.com/buy/browse/v1/item_summary/search?q=generators&category_ids=12576&filter=conditions:{NEW}&aspect_filter=categoryId:12576,Brand:{Dayton}&limit=50&offset=0&sort=-price
 *
 * https://api.ebay.com/buy/browse/v1/item_summary/search?q=switchgear&category_ids=12576&
 * aspect_filter=categoryId:12576,Brand:{COOPER}
 *
 * @return  string  API endpoint (url)
 **/
function construct_api_endpoint($search_keyword_phrase, $params) {
    $category_id = "12576";
    $api_endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . urlencode($search_keyword_phrase);
    $api_endpoint .= "&category_ids=" . $category_id;

    // Build filters
    $filters = [];
    if (!empty($params['condition'])) {
        $filters[] = 'conditions:{' . $params['condition'] . '}';
    }
    if (!empty($params['min_price']) || !empty($params['max_price'])) {
        $min_price = $params['min_price'] ?: '0';
        $max_price = $params['max_price'] ?: '999999';
        $filters[] = 'price:[' . $min_price . '..' . $max_price . '],priceCurrency:USD';
    }
    if (!empty($filters)) {
        $api_endpoint .= "&filter=" . urlencode(implode(",", $filters));
    }

    // Build aspect_filter
    $aspect_filters = ["categoryId:{$category_id}"];
    if (!empty($params['manufacturer'])) {
        $aspect_filters[] = "Brand:{" . $params['manufacturer'] . "}";
    }
    if (!empty($aspect_filters)) {
        $api_endpoint .= "&aspect_filter=" . urlencode(implode(",", $aspect_filters));
    }

    $api_endpoint .= "&limit=50&offset=" . (($params['pg'] - 1) * 50) . "&sort=" . (($params['sort_select'] === 'price_asc') ? 'price' : '-price');
    error_log("API ENDPOINT = " . urldecode($api_endpoint)); //TESTING
    return $api_endpoint;
}

/**
 * Gets the data from eBay via the API call
 *
 * @return  json with the data
 **/
function fetch_ebay_data($api_endpoint, $auth_token) {
    $headers = ['Authorization:Bearer ' . $auth_token];
    $connection = curl_init();
    curl_setopt($connection, CURLOPT_URL, $api_endpoint);
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($connection);
    curl_close($connection);
    return json_decode($response);
}
