<?php
/** Custom Shortcode added by Burton 8-31-2024
 *
 * This includes the code to take parameters sent via URL and
 * make a call to eBay's API to get search results then
 * display the results
 * 
 * v6.0 (02/13/2025): Pulled out of Snippet plugin in WP
 **/

include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/ebay_api_call_functions.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/pagination_functions.php');

/**
 * Renders the html to build the page
 * 
 * @return false|string
 **/
function render_results_page($response_decoded, $params) {
    ob_start();
    error_log(print_r($params, true)); //TESTING
    $total_results = isset($response_decoded->total) ? (int)$response_decoded->total : 0;
    if ($total_results >= 10000) {
        echo "<h4>Over 10,000 products found</h4>It is recommended that you refine the search using the filters below.<br><br>";
    } else {
        echo "<h4>" . ($total_results > 0 ? number_format($total_results) . ' Products Found' : 'No products found. Please refine search.') . "</h4>";
    }
    /////////////////////////////
    /// TODO: Break this out into function

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
    error_log("SPECIAL FILTERS VAR: " . print_r($special_filters, true)); //TESTING

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

/**
 * This is the main shortcode function called by the site when the results
 * of the ebay api call are desired to be displayed
 */
function api_ebay_call_shortcode() {
    $current_query = get_search_parameters();
    $auth_token = get_ebay_oauth_token();

    if (strpos($auth_token, "ERR") === 0) {
        die("Internal error. Token Failure");
    }

    $pagination = get_pagination_parameters();
    $search_keyword_phrase = build_search_keyword_phrase($current_query);

    // Modify API endpoint to include pagination offset
    $api_endpoint = construct_api_endpoint($search_keyword_phrase, $current_query, $pagination['offset'], $pagination['results_per_page']);

    $response_decoded = fetch_ebay_data($api_endpoint, $auth_token);

    // Render results with pagination
    return render_results_page($response_decoded, $current_query) . render_pagination_links($response_decoded->total, $pagination['current_page'], $pagination['results_per_page']);
}

add_shortcode('api_ebay_call', 'api_ebay_call_shortcode');
