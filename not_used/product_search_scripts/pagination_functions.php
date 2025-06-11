<?php

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

