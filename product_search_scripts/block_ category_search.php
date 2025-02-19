<?php
/**
 * This shortcode will put in a search block. It was built for the Product Category page. 
 * 
 * v2.0 -- Revised to build pull downs from text file lists
 * v3.0 -- Added other filters
 * v3.1 -- Added shortcode parameter to facilitate scaling and multiple forms per page
 * v4.0 -- Added ability to customize search terms for menu items using <> in text files
 * v5.0 -- Added in special case search elements
 * v6.0 -- Revised to have dynamic population of Manufacturer pull-down menu based on Type selection
 * 
 **/

// Include custom functions
include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/list_handler.php');
include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_functions.php');
include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/special_case_form_element_functions.php');
include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/search_filter_scripts.php');

		
/* Main Shortcode Function */
function advanced_product_search_form_shortcode($atts) {
	//Set up unique instance id number so multiple forms can work on same page
	static $formCount = 0;
    $formCount++;
	
	// Retrieve the category passed in the shortcode	
	$atts = shortcode_atts([
        'category' => 'industrial' // default if none provided
    ], $atts, 'product_search_form'); 
    $product_category = $atts['category'];
	
	//Get condition and check either USED or NEW on form
	$condition = isset($_GET['condition']) ? $_GET['condition'] : '';
	
	//Set unique ID for form so this can be used to make multiple forms on one page
	$unique_id = 'form_' . $formCount;
	
	ob_start();	
    ?>
    <div id="advanced-product-search-form-<?php echo $unique_id; ?>" style="padding: 20px; border: 1px solid #ccc;">
              
        <!-- Build out main form elements common to all forms -->
		<?php
			// Collect special filter html and keys
			$specialFilters = add_special_filter_elements($product_category, $unique_id);	
			$specialFilterHTML = $specialFilters['html']; 
			$specialFilterKeys = $specialFilters['keys']; 
			
			// Display form elements
			echo add_condition_element($product_category, $unique_id, $condition); 
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

    // Include JavaScript
    echo get_search_script($unique_id, $product_category, $specialFilterKeys);
    return ob_get_clean();

}
add_shortcode('product_search_form', 'advanced_product_search_form_shortcode');
