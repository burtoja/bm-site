<?php
/** BEGIN
 * Custom Shortcode added by Burton 8-31-2024
 *
 * v4.0 (12-31-2024): Added pagination
 * v4.1 (01-01-2025): Fixed encoding problem with pagination due to reserved 'page' variable
 * v4.2 (01-01-2025): Added page links and styled
 *
 **/

function api_ebay_call_shortcode() {
    // Get search keyword and page number from URL
    $current_query = $_GET;
    $search_keyword_phrase = isset($_GET['k']) ? $_GET['k'] : '';

    // Update or add the page parameter
    $current_page = isset($current_query['pg']) ? (int)$current_query['pg'] : 1;

    // Set page offset and page limit
    $results_per_page = 50; // Adjust as needed
    $offset = ($current_page - 1) * $results_per_page;

    // Determine condition filter based on referrer
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $condition_filter = '';
    if ($referrer == "https://boilersandmachinery.com/product-index-used/") {
        $page_header_text = "Used Products";
        $condition_filter = "USED";
    } elseif ($referrer == "https://boilersandmachinery.com/product-index-new/") {
        $page_header_text = "New Products";
        $condition_filter = "NEW";
    } else {
        $page_header_text = "New and Used Products";
    }

    // Get OAuth token
    include ($_SERVER["DOCUMENT_ROOT"] . '/ebay_oauth/getBasicToken.php');
    $auth_token = getBasicOauthToken();
    if (strpos($auth_token, "ERR") === 0) {
        die("Internal error. Token Failure");
    }

    // Setup API endpoint
    $cleaned_keyword = urlencode($search_keyword_phrase);
    $api_endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search?q={$cleaned_keyword}&category_ids=12576&filter=conditions:{{$condition_filter}}&limit={$results_per_page}&offset={$offset}&sort=-price";

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
            $pic = $item->image->imageUrl;
            $link = $item->itemWebUrl;
            $title = $item->title;
            $price = $item->price->value;

            $resultsTableRows .= "<tr><td><img src=\"$pic\"></td><td><a href=\"$link\">$title</a> - \$$price</td></tr>";
            $result_count++;
        }
    }

    // Calculate total pages
    $total_pages = ceil($total_results / $results_per_page);

    // Generate numbered pagination
    $pagination = '<div class="pagination">';
    if ($total_pages > 1) {
        $pagination .= '<p>Total Pages: ' . $total_pages . '</p>';

        // "Previous" link
        if ($current_page > 1) {
            $pagination .= '<a href="?k=' . urlencode($search_keyword_phrase) . '&pg=' . ($current_page - 1) . '">Previous</a>';
        }

        // Determine the range of page links to display
        $max_links = 10; // Show 10 page links at a time
        $start_page = max(1, $current_page - floor($max_links / 2));
        $end_page = min($total_pages, $start_page + $max_links - 1);

        // Adjust if we're near the beginning or end
        if ($end_page - $start_page < $max_links - 1) {
            $start_page = max(1, $end_page - $max_links + 1);
        }

        // Generate page links
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                $pagination .= '<span class="current-page">' . $i . '</span>';
            } else {
                $pagination .= '<a href="?k=' . urlencode($search_keyword_phrase) . '&pg=' . $i . '">' . $i . '</a>';
            }
        }

        // "Next" link
        if ($current_page < $total_pages) {
            $pagination .= '<a href="?k=' . urlencode($search_keyword_phrase) . '&pg=' . ($current_page + 1) . '">Next</a>';
        }
    }
    $pagination .= '</div>';

    ob_start();
    ?>
    <h4><?php echo number_format($total_results) . ' ' . $page_header_text; ?> Found</h4>
    <?php echo $pagination; ?>
    <table>
        <?php echo $resultsTableRows; ?>
    </table>
    <?php echo $pagination; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('api_ebay_call', 'api_ebay_call_shortcode');
