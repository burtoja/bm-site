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

    //$search_keyword_phrase = isset($_GET['k']) ? $_GET['k'] : '';
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

/**
 * Extracts and manages pagination parameters from the request.
 *
 * @return array Associative array with pagination details (current page, offset, results per page)
 */
function get_pagination_parameters() {
    $current_query = $_GET;
    $current_page = isset($current_query['pg']) ? (int)$current_query['pg'] : 1;
    $results_per_page = 50; // Adjust as needed
    $offset = ($current_page - 1) * $results_per_page;

    return [
        'current_page' => $current_page,
        'offset' => $offset,
        'results_per_page' => $results_per_page,
    ];
}

/**
 * Generates pagination links for the results page.
 *
 * @param int $total_results Total number of results returned from API.
 * @param int $current_page Current active page.
 * @param int $results_per_page Number of results per page.
 * @return string HTML output for pagination links.
 */
function render_pagination_links($total_results, $current_page, $results_per_page) {
    $max_results = min($total_results, 10000); // Cap at 10,000
    $total_pages = ceil($max_results / $results_per_page);
    if ($total_pages <= 1) return ''; // No pagination needed

    $pagination_html = '<div class="pagination">';

    $range = 3; // Number of pages to show on each side of current page
    $start = max(1, $current_page - $range);
    $end = min($total_pages, $current_page + $range);
    $query_params = array_merge($_GET, ['pg' => 1]); //add pg parameter to filters in url

    if ($current_page > 1) {
        //Create FIRST page link
        $query_params['pg'] = $current_page;
        $query_string = http_build_query($query_params);
        $pagination_html .= '<a href="?' . $query_string . '">First</a> ';

        //Create PREV page link
        $query_params['pg'] = $current_page - 1;
        $query_string = http_build_query($query_params);
        $pagination_html .= '<a href="?' . $query_string . '">Prev</a> ';
    }

    //Create numbered page links
    for ($i = $start; $i <= $end; $i++) {
        $query_params['pg'] = $i;
        $query_string = http_build_query($query_params);
        if ($i == $current_page) {
            $pagination_html .= '<a href="?' . $query_string . '" class="active" style="border:2px solid;">' . $i . '</a> ';
        } else {
            $pagination_html .= '<a href="?' . $query_string . '" class="active" style="border:1px solid;">' . $i . '</a> ';
        }
    }

    //Create NEXT page link
    if ($current_page < $total_pages) {
        $query_params['pg'] = $current_page + 1;
        $query_string = http_build_query($query_params);
        $pagination_html .= '<a href="?' . $query_string . '">Next</a> ';

        //Create LAST page link
        $query_params['pg'] = $total_pages;
        $query_string = http_build_query($query_params);
        $pagination_html .= '<a href="?' . $query_string . '">Last</a> ';
    }

    $pagination_html .= '</div>';
    return $pagination_html;
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
