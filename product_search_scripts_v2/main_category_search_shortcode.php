<?php
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/db_connection.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/main_category_search_filter_blocks.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts_v2/main_category_style_block.php');

function boilersa_categories_shortcode($atts) {
    $conn = get_db_connection();

    // Get all categories
    $sql = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

    ob_start();
    echo '<form id="product-filter-form" method="GET">';

    // Add sticky buttons for search and reset at the top
    echo render_sticky_search_reset_buttons();

    echo '<div class="category-list">';

    if ($result->num_rows > 0) {
        while ($cat = $result->fetch_assoc()) {
            $categoryId = (int) $cat['id'];
            $categoryName = htmlspecialchars($cat['name']);

            echo '<div class="category-item">';
            echo '<div class="toggle category-toggle" onclick="toggleVisibility(this)">[+] ' . $categoryName . '</div>';
            echo '<div class="category-filters" style="display:none;">';

            // Add Condition Filter (New/Used)
            echo render_condition_filter($categoryId);
            // Add "Price Range" as a toggleable filter
            echo render_price_range_filter($categoryId);
            // Add "Sort Order" as a toggleable filter
            echo render_sort_order_filter($categoryId);
            // Add filters linked to this category from DB
            echo render_category_filters_from_db($categoryId, $conn);
        }
    } else {
        echo '<p>No categories found.</p>';
    }

    echo '</div>';

    $conn->close();

    // Add CSS
    echo render_main_category_listing_style_block();

    echo '</form>';

    // Add JS (be sure these are in order)
    echo '<script src="/product_search_scripts_v2/main_category_search_toggle_visibility.js"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_toggle_custom_price.js"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_reset_filters_button_action.js"></script>';



    //Adding timestamp to this one to force clean cache for testing.  Can return to normal later
    //    echo '<script src="/product_search_scripts_v2/main_category_search_submit_button_listener.js"></script>';
    //    echo '<script src="/product_search_scripts_v2/main_category_search_collect_filters.js"></script>';
    //    echo '<script src="/product_search_scripts_v2/main_category_search_extract_search_params.js"></script>';
    //    echo '<script src="/product_search_scripts_v2/main_category_search_build_query_string.js"></script>';
    $ver = time(); // or use a hardcoded version like '1.2'
    echo '<script src="/product_search_scripts_v2/main_category_search_submit_button_listener.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_collect_filters.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_extract_search_params.js?v=' . $ver . '"></script>';
    echo '<script src="/product_search_scripts_v2/main_category_search_build_query_string.js?v=' . $ver . '"></script>';



    return ob_get_clean();
}
add_shortcode('boilersa_categories', 'boilersa_categories_shortcode');
?>
