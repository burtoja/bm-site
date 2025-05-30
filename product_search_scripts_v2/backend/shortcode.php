<?php
// shortcode.php - with Alpine-powered nested filter UI

include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');

function boilersa_categories_shortcode($atts) {
    $conn = get_db_connection();

    ob_start();

    echo '<div class="product-search-grid">';

//// Left Column – FILTERS ////
    echo '<div class="filters-column">';
    echo '<div id="product-filter-form">';

    // Sticky buttons if needed
    // echo render_sticky_search_reset_buttons();

    // Load Alpine-based filter tree HTML from external file
    echo file_get_contents($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/frontend/filter_tree_component.html');

    echo '</div>';
    echo '</div>'; // close .filters-column

//// Right Column – RESULTS ////
    echo '<div class="results-column">';
    echo '<div id="search-results"><p>Search results will appear here....</p></div>';
    echo '<div id="pagination"></div>';
    echo '</div>'; // close .results-column

    echo '</div>'; // close .product-search-grid

    $conn->close();

    // Enqueue Alpine + your frontend scripts
    $ver = time(); // cache-busting

    // Add tailwind
    echo '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';
    echo '<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">';
    echo '<script src="/product_search_scripts_v2/frontend/build_query_from_selections.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/filter_tree_controller.js?v=' . $ver . '"></script>';

    // Other search behavior scripts (if needed for results)
    echo '<script src="/product_search_scripts_v2/frontend/render_results.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/render_pagination.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/fetch-ebay-data.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/run_search_with_offset.js?v=' . $ver . '"></script>';

    return ob_get_clean();
}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');
