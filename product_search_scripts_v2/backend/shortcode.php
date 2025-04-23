<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/backend/filter_blocks.php');

function boilersa_categories_shortcode($atts) {
    $conn = get_db_connection();

    // Get all categories
    $sql = "SELECT id, name, has_subcategories FROM categories ORDER BY name ASC";
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
            $hasSubcategories = (bool) $cat['has_subcategories'];

            echo '<div class="category-item">';
            echo '<div class="toggle category-toggle" onclick="selectCategory(this)">[+] ' . $categoryName . '</div>';
            echo '<div class="category-filters" style="display:none;">';

            echo render_condition_filter($categoryId);
            echo render_price_range_filter($categoryId);
            echo render_sort_order_filter($categoryId);
            if ($hasSubcategories) {
                // Subcategories will be loaded dynamically via JS
                echo "<div class='subcategory-container' data-category-id='{$categoryId}'></div>";
            } else {
                // Regular flat filters
                echo render_category_filters_from_db($categoryId, $conn);
            }

            echo '</div>'; // close .category-filters
            echo '</div>'; // close .category-item
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
    echo '<div id="pagination"></div>';
    echo '</div>'; // close .results-column

    echo '</div>'; // close .product-search-grid

    $conn->close();

    // Add JS (be sure these are in order)
    echo '<script src="/product_search_scripts_v2/frontend/toggle_filter_visibility.js"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/toggle_custom_price.js"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/reset_button_action.js"></script>';

    //Adding timestamp to this one to force clean cache for testing.
    $ver = time(); // or use a hardcoded version like '1.2'
    echo '<script src="/product_search_scripts_v2/frontend/select_category.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/collect_filters.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/extract_search_params.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/build_query.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/build_api_endpoint_from_params.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/fetch-ebay-data.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/submit_button_listener.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/render_results.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/render_pagination.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/frontend/run_search_with_offset.js?v=' . $ver . '"></script>';

    return ob_get_clean();
}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');
?>
