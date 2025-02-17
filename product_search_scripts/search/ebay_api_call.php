/** BEGIN 
 * Custom Shortcode added by Burton 8-31-2024  
 * 
 * v4.0 (12-31-2024): Added pagination
 * v4.1 (01-01-2025): Fixed encoding problem with pagination due to reserved 'page' variable
 * v4.2 (01-01-2025): Added page links and styled
 * v4.3 (01-08-2025): Modified to handle additional query parameters
 * v4.4 (01-08-2025): Incorporated min/max price, sort options, and zip/radius
 * v5.0 (01-20-2025): Adding filtering capability
 * v5.1 (01-20-2025): Reverted to local filter rather than eBay based
 **/

function api_ebay_call_shortcode() {
    // Get search parameters from the URL
    error_log("START");
    $current_query = $_GET;
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

    // Min/Max Price
    $min_price = isset($_GET['min_price']) ? trim($_GET['min_price']) : '';
    $max_price = isset($_GET['max_price']) ? trim($_GET['max_price']) : '';

	// Sorting
    $sort_select = isset($_GET['sort_select']) ? $_GET['sort_select'] : 'price_desc';
    // Map to eBay's expected param (e.g., 'price' or '-price')
    $sort_param = ($sort_select === 'price_asc') ? 'price' : '-price';

    // Update or add the page parameter
    $current_page = isset($current_query['pg']) ? (int)$current_query['pg'] : 1;

    // Set page offset and page limit
    $results_per_page = 50; // Adjust as needed
    $offset = ($current_page - 1) * $results_per_page;

    // Get OAuth token
    include ($_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php');
    $auth_token = getBasicOauthToken();
    if (strpos($auth_token, "ERR") === 0) {
        die("Internal error. Token Failure");
    }

    // Build the combined search keyword phrase
    // (Appends manufacturer, type, and additional filters to the main search phrase)
    if (!empty($manufacturer)) {
        $search_keyword_phrase .= "+" . $manufacturer;
    }
    if (!empty($type)) {
        $search_keyword_phrase .= "+" . $type;
    }
    if (!empty($additional_filters)) {
        $search_keyword_phrase .= "+" . $additional_filters;
    }
	if (!empty($special_filters)) {	
		foreach ($special_filters as $NameValuePair) {
			$parts = explode('=', $NameValuePair);
			//$specialFilterName = $parts[0];
			$specialFilterValue = $parts[1];
			$search_keyword_phrase .= "+" . $specialFilterValue;			
		}
    }

    // We'll encode, then restore plus signs
    $search_keyword_phrase = str_replace('%2B', '+', rawurlencode($search_keyword_phrase));
	$search_keyword_phrase = str_replace('+and', '', $search_keyword_phrase);
	error_log("Search Keyword Phrase: " . $search_keyword_phrase);
	

    // -----------------------------------------------
    // Construct the main API endpoint
    // ----------------------------------------------- 
    // https://api.ebay.com/buy/browse/v1/item_summary/search?q=...
    $ebay_category_id="12576";
	$api_endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $search_keyword_phrase;
    $api_endpoint .= "&category_ids=" . $ebay_category_id; 

    // Build up an array of eBay filters
    // (We’ll join them with a comma or " AND " as needed.)
    $filters = [];

    // 1) Condition	
	if (!empty($condition)) {
		$filters[] = 'conditions:{' . $condition . '}';
	}

    // 2) Price Range
	if (!empty($min_price) && !empty($max_price)) {
		$filters[] = 'price:[' . $min_price . '..' . $max_price . '],priceCurrency:USD';
	} elseif (!empty($min_price)) {
		$filters[] = 'price:[' . $min_price . '],priceCurrency:USD';
	} elseif (!empty($max_price)) {
		$filters[] = 'price:[0..' . $max_price . '],priceCurrency:USD';
	}
	
	// Join condition and price range filters with commas
	if (!empty($filters)) {
		$filter_string = implode(',', $filters); 
		// e.g. "conditions:("USED"),price:[0..100]"
		$api_endpoint .= "&filter=" . rawurlencode($filter_string);
	}
	error_log("Filter String: " . $filter_string);
	
    // Add pagination & sorting
    $api_endpoint .= "&limit={$results_per_page}&offset={$offset}&sort={$sort_param}";
	
    // Debugging
    error_log("Full eBay API Endpoint: " . $api_endpoint);

    // Create headers
    $headers = array(
        'Authorization:Bearer ' . $auth_token
    );

    // Send request to eBay and load response
    $connection = curl_init();
    curl_setopt($connection, CURLOPT_URL, $api_endpoint);
    curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($connection);

    // Check for cURL errors
    if (curl_errno($connection)) {
        error_log("cURL Error: " . curl_error($connection));
        die('Error connecting to eBay API');
    }

    curl_close($connection);

    // Handle response
    if (!$response) {
        die('Error fetching data from eBay API');
    }

    $response_decoded = json_decode($response);
    $resultsTableRows = '';
    $result_count = 0;
    $total_results = isset($response_decoded->total) ? (int)$response_decoded->total : 0;

    if (isset($response_decoded->itemSummaries)) {
        foreach ($response_decoded->itemSummaries as $item) {
            $pic = $item->image->imageUrl ?? '';
            $link = $item->itemWebUrl ?? '';
            $title = $item->title ?? '';
            $price = $item->price->value ?? '0.00';

            $resultsTableRows .= "<tr><td><img src=\"$pic\" style='max-width:100px;'></td><td><a href=\"$link\" target=\"_blank\">$title</a> - \$$price</td></tr>";
            $result_count++;
        }
    }

    // Calculate total pages
    $total_pages = ($total_results > 0) ? ceil($total_results / $results_per_page) : 1;

    // Generate numbered pagination
    $pagination = '<div class="pagination">';
    if ($total_pages > 1) {
        $pagination .= '<p>Total Pages: ' . $total_pages . '</p>';

        // "Previous" link
        if ($current_page > 1) {
            $pagination .= '<a href="' . build_pagination_url($search_keyword_phrase, $condition, $manufacturer, $type, $additional_filters, $min_price, $max_price, $sort_select, $zip, $radius, $current_page - 1) . '">Previous</a>';
        }

        // Determine the range of page links to display
        $max_links = 10;
        $start_page = max(1, $current_page - floor($max_links / 2));
        $end_page = min($total_pages, $start_page + $max_links - 1);

        if ($end_page - $start_page < $max_links - 1) {
            $start_page = max(1, $end_page - $max_links + 1);
        }

        // Generate page links
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                $pagination .= '<span class="current-page">' . $i . '</span>';
            } else {
                $pagination .= '<a href="' . build_pagination_url($search_keyword_phrase, $condition, $manufacturer, $type, $additional_filters, $min_price, $max_price, $sort_select, $zip, $radius, $i) . '">' . $i . '</a>';
            }
        }

        // "Next" link
        if ($current_page < $total_pages) {
            $pagination .= '<a href="' . build_pagination_url($search_keyword_phrase, $condition, $manufacturer, $type, $additional_filters, $min_price, $max_price, $sort_select, $zip, $radius, $current_page + 1) . '">Next</a>';
        }
    }
    $pagination .= '</div>';

    ob_start();
	
	if (number_format($total_results) > 0) {
		$product_count_message = number_format($total_results) . ' Products Found';
	} else {
		$product_count_message = 'No products were found matching your search criterea.  Please refine search and try again.';
	}

	//Build results filter box for top of page
	include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/collapsible_filter_box.php');
	echo displayCollapsibleFilterBox($product_category, 'results1', $selectedSpecialArr);
	
    ?>
    <h4><?php echo $product_count_message; ?></h4>
    <?php echo $pagination; ?>
    <table>
        <?php echo $resultsTableRows; ?>
    </table>
    <?php echo $pagination; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('api_ebay_call', 'api_ebay_call_shortcode');

/**
 * Build a pagination URL preserving all query parameters.
 * 
 * Adjust as needed to ensure all parameters appear in the URL for each page link.
 */
function build_pagination_url($search_keyword_phrase, $condition, $manufacturer, $type, $additional_filters, $min_price, $max_price, $sort_select, $zip, $radius, $page_number) {
    $params = [
        'k'          => urldecode($search_keyword_phrase), // or might keep as-is
        'condition'  => $condition,
        'manufacturer' => $manufacturer,
        'type'       => $type,
        'filters'    => $additional_filters,
        'min_price'  => $min_price,
        'max_price'  => $max_price,
        'sort_select'=> $sort_select,
        //'zip'        => $zip,
        //'radius'     => $radius,
        'pg'         => $page_number
    ];
    // Build the query
    $query_string = http_build_query($params);
    return '?' . $query_string;
}
