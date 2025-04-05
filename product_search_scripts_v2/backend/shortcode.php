<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/filter_blocks.php');

function boilersa_categories_shortcode($atts) {
    $conn = get_db_connection();

    // Get all categories
    $sql = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);
    error_log("Error Log Active (shortcode)");
    ob_start();
    echo '<div class="product-search-grid">';

//// Left Column – FILTERS ////
    echo '<div class="filters-column">';
    echo '<form id="product-filter-form" method="GET">';

// Sticky Search + Reset buttons
    echo render_sticky_search_reset_buttons();

    echo '<div class="category-list">';

    if ($result->num_rows > 0) {
        while ($cat = $result->fetch_assoc()) {
            $categoryId = (int) $cat['id'];
            $categoryName = htmlspecialchars($cat['name']);

            echo '<div class="category-item">';
            echo '<div class="toggle category-toggle" onclick="selectCategory(this)">[+] ' . $categoryName . '</div>';
            //echo '<div class="toggle category-toggle" onclick="toggleVisibility(this)">[+] ' . $categoryName . '</div>';
            echo '<div class="category-filters" style="display:none;">';

            echo render_condition_filter($categoryId);
            echo render_price_range_filter($categoryId);
            echo render_sort_order_filter($categoryId);
            echo render_category_filters_from_db($categoryId, $conn);

        }
    } else {
        echo '<p>No categories found.</p>';
    }

    echo '</div>'; // close category-list
    echo '</form>';
    echo '</div>'; // close .filters-column


//// Right Column – RESULTS ////
    echo '<div class="results-column">';
    echo '<div id="search-results"><p>Search results will appear here...</p></div>';
    echo '</div>'; // close .results-column

    echo '</div>'; // close .product-search-grid

    $conn->close();

    // Add JS (be sure these are in order)
    echo '<script src="/product_search_scripts_v2/frontend/main_category_search_toggle_visibility.js"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/main_category_search_toggle_custom_price.js"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/main_category_search_reset_filters_button_action.js"></script>';

    //Adding timestamp to this one to force clean cache for testing.
    $ver = time(); // or use a hardcoded version like '1.2'
    echo '<script src="/product_search_scripts_v2/main_category_search_select_category.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/collect_filters.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_extract_search_params.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/build_query.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/build_api_endpoint_from_params.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/fetch-ebay-data.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_submit_button_listener.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_render_results.js?v=' . $ver . '"></script>';



    return ob_get_clean();
}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');
?>
