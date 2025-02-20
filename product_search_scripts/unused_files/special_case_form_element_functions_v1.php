<?php
/**
 * These function takes care of building out special case elements 
 * of the form.  
 * 
 * Adding new filters:
 * 1-Create function and/or add filters to that function
 * 2-Add if statement to add_special_filter_elements function
 * 3-appenf $form_element with the function
 * 4-Add to $specialFilterKeys array
 * 
 * Be sure to include the associated scripts at the bottom of any file
 * these elements are used on.  File naming convention should be followed:
 * list_{category}_special_{special filter type}.txt
 * 
 * $product_category the category of the product which 
 * should come from the referring page
 * 
 * $unique_id the id used to differentiate between multiple 
 * forms on the same page
 **/

/**
 * Decides which (if any) special elements need to be added to the form
 * and pre-selects them if matching keys exist in $selectedValues.
 *
 * @param string $product_category
 * @param string $unique_id
 * @param array  $selectedValues   associative array of special_filter_type => selectedOverride
 *                                 e.g. ['housing_material' => 'steel', 'media' => 'water']
 */
function add_special_filter_elements($product_category, $unique_id, $selectedValues = []) {
    $form_element = "";
    $specialFilterKeys = [];
    error_log("SELECTED VALUES ARRAR FROM add_special_filter_elements(): " . print_r($selectedValues, true));

    if ($product_category == "pumps") {
        $form_element .= add_pump_media_type_element($product_category, $unique_id, $selectedValues);
        $specialFilterKeys = [
          "housing_material",
          "impeller",
          "inlet_diameter",
          "outlet_diameter",
          "media",
          "mounting_position",
          "phases",
          "power_rating",
          "power_source",
          "priming",
          "stages",
          "suction",
          "supply_voltage"
        ];
    }
    if ($product_category == "belts") {
        $form_element .= add_belt_material_type_element($product_category, $unique_id, $selectedValues);
        $specialFilterKeys = [
          "material"
        ];
    }
    if ($product_category == "boilers") {
        $form_element .= add_boiler_special_element($product_category, $unique_id, $selectedValues);
        $specialFilterKeys = [
          "fuel",
          "medium",
          "configuration"
        ];
    }
    if ($product_category == "cooling_towers") {
        $form_element .= add_cooling_tower_media_type_element($product_category, $unique_id, $selectedValues);
        $specialFilterKeys = [
          "media"
        ];
    }
    if ($product_category == "fuses") {
        $form_element .= add_fuse_special_type_element($product_category, $unique_id, $selectedValues);
                $specialFilterKeys = [
          "current"
        ];
    }
    if ($product_category == "switchgear") {
        $form_element .= add_switchgear_special_type_element($product_category, $unique_id, $selectedValues);
                $specialFilterKeys = [
          "other_filters"
        ];
    }
    if ($product_category == "generators") {
        $form_element .= add_generator_special_type_element($product_category, $unique_id, $selectedValues);
                $specialFilterKeys = [
          "fuel_type"
        ];
    }
    
    return [
        'html' => $form_element,
        'keys' => $specialFilterKeys
    ];
}


/**
 * Private helper function to build pull-down menu for these special elements.
 *
 * @param string $product_category   e.g. "pumps"
 * @param string $unique_id          e.g. "form_1"
 * @param string $special_filter_type e.g. "housing_material", "fuel", "media"
 * @param string $selectedValue      the previously selected override, if any
 */
function build_pulldown_menu($product_category, $unique_id, $special_filter_type, $selectedValue = '') {
    $menu_id = $product_category . '-special-' . $special_filter_type;
    
    // Make a user-friendly display name by replacing underscores with spaces and capitalizing words
    $menu_display_name = ucwords(str_replace('_', ' ', $special_filter_type));
    
    // Build the reference file name
    $reference_file_name = "list_" . $product_category . "_special_" . $special_filter_type . ".txt";

    $form_element = '
        <div style="margin-bottom: 16px;">
            <label for="' . $menu_id . '-' . $unique_id . '" style="display: block; margin-bottom: 8px; font-weight: bold;">
                ' . htmlspecialchars($menu_display_name) . ' Type:
            </label>
            <select id="' . $menu_id . '-' . $unique_id . '" style="padding: 8px; width: 35em; font-size: 16px;">
                <option value="">Type or Select a ' . htmlspecialchars($menu_display_name) . ' (or leave blank to see all)</option>';

    $file_path = $_SERVER["DOCUMENT_ROOT"] . '/product_info_lists/' . $reference_file_name;
    $special_types_list = get_names_and_search_terms($file_path);
    
    foreach ($special_types_list as $type) {
        $overrideVal = htmlspecialchars($type['override']);
        $displayName = htmlspecialchars($type['displayName']);

        // If this line's override matches the user's previously selected value, mark as selected
        $selected = ($type['override'] === $selectedValue) ? ' selected' : '';

        $form_element .= '<option value="' . $overrideVal . '"' . $selected . '>' . $displayName . '</option>';
    }
    
    $form_element .= '
            </select>
        </div>';
    
    return $form_element;
}



/**
 * Builds pump media type element pull-down menus
 */
function add_pump_media_type_element($product_category, $unique_id, $selectedValues = []) {
    // The array keys (e.g. 'housing_material') must match the text in build_pulldown_menu's third param
    // so that we can pass $selectedValues['housing_material'] to it, etc.

    $form_element = '';
    $form_element .= build_pulldown_menu($product_category, $unique_id, "housing_material", $selectedValues['housing_material'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "impeller", $selectedValues['impeller'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "inlet_diameter", $selectedValues['inlet_diameter'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "outlet_diameter", $selectedValues['outlet_diameter'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "media", $selectedValues['media'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "mounting_position", $selectedValues['mounting_position'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "phases", $selectedValues['phases'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "power_rating", $selectedValues['power_rating'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "power_source", $selectedValues['power_source'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "priming", $selectedValues['priming'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "stages", $selectedValues['stages'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "suction", $selectedValues['suction'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "supply_voltage", $selectedValues['supply_voltage'] ?? '');
    return $form_element;	
}


/**
 * Builds belt material type element pull-down menu
 */
function add_belt_material_type_element($product_category, $unique_id, $selectedValues = []) {
    $form_element = '';
    // If the file is "list_belts_special_material.txt", the key is "material"
    $form_element .= build_pulldown_menu($product_category, $unique_id, "material", $selectedValues['material'] ?? '');
    return $form_element;
}


/**
 * Builds boiler special element pull-down menus
 */
function add_boiler_special_element($product_category, $unique_id, $selectedValues = []) {
    $form_element = '';
    $form_element .= build_pulldown_menu($product_category, $unique_id, "fuel", $selectedValues['fuel'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "medium", $selectedValues['medium'] ?? '');
    $form_element .= build_pulldown_menu($product_category, $unique_id, "configuration", $selectedValues['configuration'] ?? '');
    return $form_element;
}

 
/**
 * Builds cooling tower media type element pull-down menu
 */
function add_cooling_tower_media_type_element($product_category, $unique_id, $selectedValues = []) {
    $form_element = '';
    // Typically "media" 
    $form_element .= build_pulldown_menu($product_category, $unique_id, "media", $selectedValues['media'] ?? '');
    return $form_element;	
}


/**
 * Builds fuse special filter element pull-down menu
 */
function add_fuse_special_type_element($product_category, $unique_id, $selectedValues = []) {
    $form_element = '';
    // "current" is the filter type
    $form_element .= build_pulldown_menu($product_category, $unique_id, "current", $selectedValues['current'] ?? '');
    return $form_element;	
}

/**
 * Builds switchgear special filter element pull-down menu
 */
function add_switchgear_special_type_element($product_category, $unique_id, $selectedValues = []) {
    $form_element = '';
    // "current" is the filter type
    $form_element .= build_pulldown_menu($product_category, $unique_id, "other_filters", $selectedValues['current'] ?? '');
    return $form_element;	
}

/**
 * Builds generator special filter element pull-down menu
 */
function add_generator_special_type_element($product_category, $unique_id, $selectedValues = []) {
    $form_element = '';
    // "current" is the filter type
    $form_element .= build_pulldown_menu($product_category, $unique_id, "fuel_type", $selectedValues['current'] ?? '');
    return $form_element;	
}

 
 



