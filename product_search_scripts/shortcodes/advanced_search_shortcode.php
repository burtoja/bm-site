<?php
// advanced_search_shortcode.php

// Includes for helper functions
include_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/list_handler.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_functions.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/special_case_form_element_functions.php';

// Optionally, includes for snippet sub-files
include_once __DIR__ . '/search_button_snippet.php';
// etc.

/**
 * Main Shortcode Function
 */
function advanced_product_search_form_shortcode($atts) {
    static $formCount = 0;
    $formCount++;

    $atts = shortcode_atts([
        'category' => 'industrial'
    ], $atts, 'product_search_form');
    $product_category = $atts['category'];

    $condition = isset($_GET['condition']) ? $_GET['condition'] : '';
    $unique_id = 'form_' . $formCount;

    // Start output
    ob_start();
    ?>
    <div id="advanced-product-search-form-<?php echo $unique_id; ?>" style="padding:20px; border:1px solid #ccc;">

    <?php
        // 1) If you want special filter elements
        $specialFilters = add_special_filter_elements($product_category, $unique_id);
        $specialFilterHTML = $specialFilters['html'];
        $specialFilterKeys = $specialFilters['keys'];

        // 2) Display form elements
        echo add_condition_element($product_category, $unique_id, $condition);
        echo add_type_element($product_category, $unique_id);
        echo add_manufacturer_element($product_category, $unique_id);
        echo $specialFilterHTML;
        echo add_search_box_element($unique_id);
        echo add_price_filter_elements($unique_id);
        echo add_sort_by_element($unique_id);

        // 3) Add search button
        // If you extracted the search button logic to "search_button_snippet.php", you might do:
        // echo build_search_button_snippet($unique_id, $product_category, $specialFilterKeys);
        // Otherwise, just keep it inline:
        echo add_search_button($unique_id);
        echo build_search_button_snippet($unique_id, $product_category, $specialFilterKeys);
    ?>
    </div>

    <script>
      const product_category_<?php echo $unique_id; ?> = '<?php echo esc_js($product_category); ?>';
      <?php include include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_bottom_scripts.js') ?>

      const specialFilterKeys_<?php echo $unique_id; ?> = <?php echo json_encode($specialFilterKeys); ?>;
      console.log("Special filters for category:", specialFilterKeys_<?php echo $unique_id; ?>);

      // Add your type-changed code, min_price code, or any snippet
      // ...
    </script>
    
;
    
    <?php
    return ob_get_clean();
}

// Finally, register the shortcode
add_shortcode('product_search_form', 'advanced_product_search_form_shortcode');
