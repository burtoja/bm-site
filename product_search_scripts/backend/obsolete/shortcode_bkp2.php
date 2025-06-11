<?php
// shortcode.php - Updated for collapsible tree subcategory structure
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/db_connection.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/backend/filter_blocks.php');

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
            echo "<!-- START CATEGORY: $categoryName -->";
            echo '<div class="toggle category-toggle" onclick="loadTopLevelSubcategories(' . $categoryId . ', this)">[+] ' . $categoryName . '</div>';
            echo '<div id="category-filters-' . $categoryId . '" class="category-filters" style="display:none;">';

            echo render_condition_filter($categoryId);
            echo render_price_range_filter($categoryId);
            echo render_sort_order_filter($categoryId);

            echo '<div class="subcategory-tree-container" data-category-id="' . $categoryId . '"></div>';
            echo '<div class="subcategory-filters-output"></div>';

            echo '<div id="filters-output" class="filters-output"></div>';


            echo '</div>'; // close .category-filters
            echo "<!-- END CATEGORY: $categoryName -->";
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
    echo '<script src="/product_search_scripts/frontend/toggle_filter_visibility.js"></script>';
    echo '<script src="/product_search_scripts/frontend/toggle_custom_price.js"></script>';
    echo '<script src="/product_search_scripts/frontend/reset_button_action.js"></script>';

    $ver = time(); // cache-busting version
    echo '<script src="/product_search_scripts/frontend/load_nested_subcategories.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/collect_filters.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/extract_search_params.js?v=' . $ver . '" ></script>';
    echo '<script src="/product_search_scripts/frontend/build_query.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/build_api_endpoint_from_params.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/fetch-ebay-data.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/submit_button_listener.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/render_results.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/render_pagination.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts/frontend/run_search_with_offset.js?v=' . $ver . '"></script>';

    return ob_get_clean();
}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');
?>
