<?php
// collapsible_filter_box.php

// Include common search functions and scripts
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_scripts.php');

$search_params = get_search_parameters();

/**
 * Displays a collapsible, pre-populated version of your existing search box.
 * 
 * @param string $product_category   e.g. 'industrial' or 'pumps' or 'cooling_towers'
 * @param string $unique_id          optional unique ID for multiple instances
 */
function displayCollapsibleFilterBox($product_category, $unique_id = 'collapsible1', $selectedSpecial = []) {
    global $search_params;
    
    $condition = $search_params['condition'];
    $manufacturer = $search_params['manufacturer'];
    
    include SEARCH_FORM_TEMPLATE;
}
