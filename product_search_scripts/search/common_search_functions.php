<?php
// common_search_functions.php

// Include necessary search helper functions
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/list_handler.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_functions.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/special_case_form_element_functions.php');

static $scriptLoaded = false;  // Ensure scripts are loaded only once

// Function to handle retrieving common parameters from $_GET
function get_search_parameters() {
    return [
        'condition' => isset($_GET['condition']) ? $_GET['condition'] : '',
        'manufacturer' => isset($_GET['manufacturer']) ? trim($_GET['manufacturer']) : '',
        'keyword' => isset($_GET['k']) ? $_GET['k'] : '',
        'category' => isset($_GET['category']) ? $_GET['category'] : ''
    ];
}

// Common function to generate and render a search form
function render_search_form($atts = []) {
    static $formCount = 0;
    $formCount++;

    // Retrieve the category passed in the shortcode
    $atts = shortcode_atts([
        'category' => 'industrial' // default if none provided
    ], $atts, 'product_search_form');

    $product_category = $atts['category'];
    $search_params = get_search_parameters();
    $unique_id = 'form_' . $formCount;

    ob_start();    
    ?>
    <div id="advanced-product-search-form-<?php echo $unique_id; ?>" style="padding: 20px; border: 1px solid #ccc;">
        <?php
            // Collect special filter HTML and keys
            $specialFilters = add_special_filter_elements($product_category, $unique_id);    
            $specialFilterHTML = $specialFilters['html']; 
            $specialFilterKeys = $specialFilters['keys']; 

            // Display form elements
            echo add_condition_element($product_category, $unique_id, $search_params['condition']); 
            echo add_type_element($product_category, $unique_id);
            echo add_manufacturer_element($product_category, $unique_id);
            echo $specialFilterHTML;  
            echo add_search_box_element($unique_id);
            echo add_price_filter_elements($unique_id); 
            echo add_sort_by_element($unique_id); 
            echo add_search_button($unique_id);
        ?> 
    </div>

    <?php 
    // Ensure scripts are loaded only once
    global $scriptLoaded;
    if (!$scriptLoaded) {
        include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_scripts.php');
        $scriptLoaded = true;
    }
    
    return ob_get_clean();
}
