<?php
/**
 * This function takes care of building out the elements 
 * of the form.  
 * 
 * Be sure to include the associated scripts at the bottom of any file
 * these elements are used on
 * 
 * $product_category the category of the product which 
 * should come from the referring page
 * $unique_id the id used to differentiate between multiple 
 * forms on the same page
 **/


// Builds CONDITION (NEW/USED) radio buttons
function add_condition_element($product_category, $unique_id, $condition) {
    $condition = strtoupper($condition);
    $condition_form_checked_new = "";
	$condition_form_checked_used = "";
	if ($condition == "USED") {
		$condition_form_checked_used = "checked";
	} else {
		$condition_form_checked_new = "checked";
	}
	$condition_form_default = "";
    $form_element = '';
    $form_element .= '
        <div style="margin-bottom: 16px;">
            <label style="font-weight: bold;">Condition:</label><br>
            <label>
                <input type="radio" name="condition-' . $unique_id . '" value="new" ' . $condition_form_checked_new . '> New
            </label>
            <label style="margin-left: 16px;">
                <input type="radio" name="condition-' . $unique_id . '" value="used" ' . $condition_form_checked_used . '> Used
            </label>
        </div>';
    return $form_element;    
}

/**
 * Builds the "TYPE" pull-down menu.
 *
 * @param string $product_category  e.g. "pumps", "industrial"
 * @param string $unique_id         a unique suffix to avoid ID collisions
 * @param string $selectedType      the override value that should be pre-selected (optional)
 */
function add_type_element($product_category, $unique_id, $selectedType = '') {
    // Start building the form element
    $form_element = '
        <div style="margin-bottom: 16px;">
            <label for="type-' . $unique_id . '" style="display: block; margin-bottom: 8px; font-weight: bold;">
                Product Type:
            </label>
            <select id="type-' . $unique_id . '" style="padding: 8px; width: 35em; font-size: 16px;">
                <option value="">Select a Product Type (or leave blank)</option>';

    // Load the list of types from text file
    $file_path = $_SERVER["DOCUMENT_ROOT"] . '/product_info_lists/list_' . $product_category . '_types.txt';
    $type_list = get_names_and_search_terms($file_path);

    // Loop through each type and build an <option>
    foreach ($type_list as $type) {
        $value = htmlspecialchars($type['override']);
        $display = htmlspecialchars($type['displayName']);

        // Compare with $selectedType to see if this <option> should be selected
        $selected = ($type['override'] === $selectedType) ? ' selected' : '';

        $form_element .= '<option value="' . $value . '"' . $selected . '>' . $display . '</option>';
    }

    // Close the select and container div
    $form_element .= '
            </select>
        </div>';

    return $form_element;
}



/**
 * Builds the "MANUFACTURER" pull-down menu.
 *
 * @param string $product_category    e.g. "pumps", "industrial"
 * @param string $unique_id           a unique suffix to avoid ID collisions
 * @param string $selectedManufacturer the override value that should be pre-selected (optional)
 */
function add_manufacturer_element($product_category, $unique_id, $selectedManufacturer = '') {
    $form_element = '
        <div style="margin-bottom: 16px;">
            <label for="manufacturer-' . $unique_id . '" style="display: block; margin-bottom: 8px; font-weight: bold;">
                Product Manufacturer:
            </label>
            <select id="manufacturer-' . $unique_id . '" style="padding: 8px; width: 35em; font-size: 16px;">
                <option value="">Select a Manufacturer (or leave blank to see all)</option>';

    $file_path = $_SERVER["DOCUMENT_ROOT"] . '/product_info_lists/list_' . $product_category . '_manufacturers.txt';

    $manufacturer_list = get_names_and_search_terms($file_path);
    
    foreach ($manufacturer_list as $manufacturer) {
        $value   = htmlspecialchars($manufacturer['override']);
        $display = htmlspecialchars($manufacturer['displayName']);
        
        // If this manufacturer matches the pre-selected manufacturer, mark as selected
        $selected = ($manufacturer['override'] === $selectedManufacturer) ? ' selected' : '';
        
        $form_element .= '<option value="' . $value . '"' . $selected . '>' . $display . '</option>';
    }

    $form_element .= '
            </select>
        </div>';

    return $form_element;
}



/**
 * Builds textbox for ADDITIONAL search terms
 *
 * @param string $unique_id      a unique suffix to avoid ID collisions
 * @param string $defaultFilters optional pre-filled text for the search box
 */
function add_search_box_element($unique_id, $defaultFilters = '') {
    // Escape any special characters for safe HTML output
    $safeVal = htmlspecialchars($defaultFilters, ENT_QUOTES);

    // Build the HTML
    $form_element = '
        <div style="margin-bottom: 16px;">
            <label for="additional-filters-' . $unique_id . '" style="display: block; margin-bottom: 8px; font-weight: bold;">
                Additional Search Filters (i.e., Model #, Serial #, etc.):
            </label>
            <input
                type="text"
                id="additional-filters-' . $unique_id . '"
                placeholder="Type additional search filters here"
                value="' . $safeVal . '"
                style="padding: 8px; width: 80%; font-size: 16px;"
            />
        </div>';

    return $form_element;
}



/**
 * Builds PRICE range filter elements
 *
 * @param string $unique_id             Unique suffix to avoid ID collisions
 * @param string $selectedPriceOption   Which radio button is selected: "anyPrice", "under100", or "custom"
 * @param string $defaultMin            Pre-filled minimum price
 * @param string $defaultMax            Pre-filled maximum price
 */
function add_price_filter_elements($unique_id, $selectedPriceOption = 'anyPrice', $defaultMin = '', $defaultMax = '') {
    // Determine which radio should be selected
    if ($defaultMax === '100' && ($defaultMin === '' || $defaultMin === '0')) {
        $selectedPriceOption = 'under100';
    } elseif ($defaultMin === '' && $defaultMax === '') {
        $selectedPriceOption = 'anyPrice';
    } else {
        $selectedPriceOption = 'custom';
    }

    // Escape values for safe HTML output
    $safeMin = htmlspecialchars($defaultMin, ENT_QUOTES);
    $safeMax = htmlspecialchars($defaultMax, ENT_QUOTES);

    // Determine which radio is checked
    $checkedAny      = ($selectedPriceOption === 'anyPrice')  ? 'checked' : '';
    $checkedUnder100 = ($selectedPriceOption === 'under100')  ? 'checked' : '';
    $checkedCustom   = ($selectedPriceOption === 'custom')    ? 'checked' : '';

    $form_element = '
        <div style="margin-bottom: 16px; color: green;">
            <label style="font-weight: bold;">Price Range:</label><br>
            
            <label style="font-size:14px;">
                <input type="radio" name="price_range_option-' . $unique_id . '" value="anyPrice" ' . $checkedAny . '>
                Any Price
            </label>
            <br>
            <label style="font-size:14px;">
                <input type="radio" name="price_range_option-' . $unique_id . '" value="under100" ' . $checkedUnder100 . '>
                Under $100
            </label>
            <br>
            <label style="font-size:14px;">
                <input type="radio" name="price_range_option-' . $unique_id . '" value="custom" ' . $checkedCustom . '>
                Custom Range:
            </label>
            <div style="margin-left: 24px; margin-top: 8px; font-size:14px;">
                Min Price: 
                <input 
                    type="text" 
                    id="min_price-' . $unique_id . '" 
                    style="width: 100px;"
                    value="' . $safeMin . '"
                > 
                Max Price: 
                <input 
                    type="text" 
                    id="max_price-' . $unique_id . '" 
                    style="width: 100px;"
                    value="' . $safeMax . '"
                >
            </div>
        </div>';

    return $form_element;
}



/**
 * Builds the SORT method choosing element
 *
 * @param string $unique_id   A unique suffix to avoid ID collisions
 * @param string $selectedSort Which sort option is selected, e.g. "price_desc" or "price_asc"
 */
function add_sort_by_element($unique_id, $selectedSort = 'price_desc') {
    // Determine which option is selected
    $selDesc = ($selectedSort === 'price_desc') ? 'selected' : '';
    $selAsc  = ($selectedSort === 'price_asc')  ? 'selected' : '';

    // Build the form element
    $form_element = '
        <div style="margin-bottom: 16px;">
            <label for="sort_select-' . $unique_id . '" style="font-weight: bold;">Sort by Price:</label><br>
            <select id="sort_select-' . $unique_id . '" style="padding: 2px; width: 150px; font-size: 14px;">
                <option value="price_desc" ' . $selDesc . '>High to Low</option>
                <option value="price_asc"  ' . $selAsc  . '>Low to High</option>
            </select>
        </div>';

    return $form_element;
}


// Builds the search button
function add_search_button($unique_id) {
	$form_element = '';
	$form_element .= '
        <button
            id="find-products-button-' . $unique_id . '"
            style="padding: 10px 20px; font-size: 16px; cursor: pointer; background-color: #0073aa; color: white; border: none;"
        >
            Find Products
        </button>';
     return $form_element;
} 



?>