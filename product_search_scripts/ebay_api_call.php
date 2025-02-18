<?php
/** Custom Shortcode added by Burton 8-31-2024  
 * 
 * v6.0 (02/13/2025): Pulled out of Snippet plugin in WP
 **/

include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');

function api_ebay_call_shortcode() {
    $current_query = get_search_parameters();
    $auth_token = get_ebay_oauth_token();
    
    if (strpos($auth_token, "ERR") === 0) {
        die("Internal error. Token Failure");
    }
    
    $search_keyword_phrase = build_search_keyword_phrase($current_query);
    $api_endpoint = construct_api_endpoint($search_keyword_phrase, $current_query);
    
    $response_decoded = fetch_ebay_data($api_endpoint, $auth_token);
    
    return render_results_page($response_decoded, $current_query);
}
add_shortcode('api_ebay_call', 'api_ebay_call_shortcode');



//////////////////////////////
// Function Definitions
/////////////////////////////


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
    $phrase = $params['search_keyword_phrase'];
    if (!empty($params['manufacturer'])) {
        $phrase .= "+" . $params['manufacturer'];
    }
    if (!empty($params['type'])) {
        $phrase .= "+" . $params['type'];
    }
    return str_replace('%2B', '+', rawurlencode($phrase));
}

/**
 * Constructs the API endpoint
 * 
 * @return  string  API endpoint (url)
 **/
function construct_api_endpoint($search_keyword_phrase, $params) {
    $category_id = "12576";
    $api_endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $search_keyword_phrase;
    $api_endpoint .= "&category_ids=" . $category_id;
    
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
        $api_endpoint .= "&filter=" . rawurlencode(implode(',', $filters));
    }
    $api_endpoint .= "&limit=50&offset=" . (($params['pg'] - 1) * 50) . "&sort=" . (($params['sort_select'] === 'price_asc') ? 'price' : '-price');
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

/**
 * Renders the html to build the page
 * 
 * @return  html object
 **/
function render_results_page($response_decoded, $params) {
    ob_start();
    error_log(print_r($params, true)); //TESTING
    $total_results = isset($response_decoded->total) ? (int)$response_decoded->total : 0;
    echo "<h4>" . ($total_results > 0 ? number_format($total_results) . ' Products Found' : 'No products found. Please refine search.') . "</h4>";
    
    /////////////////////////////
    /// TODO: Break this out into function

    $search_keyword_phrase = isset($_GET['k']) ? $_GET['k'] : '';
    $condition = isset($_GET['condition']) ? strtoupper($_GET['condition']) : ''; // "NEW" or "USED"
    $manufacturer = isset($_GET['manufacturer']) ? $_GET['manufacturer'] : '';
    if ($manufacturer == "Not-Specified" || $manufacturer == "Other" || $manufacturer == "Unbranded") {
        $manufacturer = '';
    }
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $additional_filters = isset($_GET['filters']) ? $_GET['filters'] : '';

    // Get special filter names and values from URL parameter list
    include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/get_special_filter_pairs_from_url.php');
    $special_filters = get_special_filter_pairs_from_url();
    error_log("SPECIAL FILTERS VAR: " . print_r($special_filters, true));

    // Build an associative array
    $selectedSpecialArr = [];
    foreach ($special_filters as $pair) {
        // $pair looks like "fuel=Oil+Fired+Boiler"
        $parts = explode('=', $pair, 2);
        if (count($parts) == 2) {
            $key = urldecode($parts[0]);
            $val = urldecode($parts[1]);
            $selectedSpecialArr[$key] = $val;
        }
    }

    ///////////////////////////END Possible function //////////////////

    //Build results filter box for top of page
	include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/collapsible_filter_box.php');
	echo displayCollapsibleFilterBox($params['k'], 'results1', $selectedSpecialArr);
    
    if (isset($response_decoded->itemSummaries)) {
        echo "<table>";
        foreach ($response_decoded->itemSummaries as $item) {
            echo "<tr><td><img src='" . ($item->image->imageUrl ?? '') . "' style='max-width:100px;'></td><td><a href='" . ($item->itemWebUrl ?? '') . "' target='_blank'>" . ($item->title ?? '') . "</a> - $" . ($item->price->value ?? '0.00') . "</td></tr>";
        }
        echo "</table>";
    }
    return ob_get_clean();
}
