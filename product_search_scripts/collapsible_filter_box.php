<?php
// collapsible_filter_box.php

// Include necessary helper files
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/list_handler.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_functions.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/special_case_form_element_functions.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/search_filter_scripts.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/search_filter_toggle_button.php');
include_once ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/common_search_functions.php');

$product_category = isset($_GET['k']) ? $_GET['k'] : '';

/**
 * Displays a collapsible, pre-populated version of your existing search box.
 *
 * @param string $product_category   e.g. 'industrial' or 'pumps' or 'cooling_towers'
 * @param string $unique_id          optional unique ID for multiple instances
 */
function displayCollapsibleFilterBox($product_category, $unique_id = 'collapsible1', $selectedSpecial = []) {
    $search_params = get_search_parameters();

    $specialReturn = add_special_filter_elements($product_category, $unique_id, $selectedSpecial);
    $specialFilterHTML = $specialReturn['html'];
    $specialKeys = $specialReturn['keys'];

    ob_start();
    echo render_toggle_search_button($unique_id);
    ?>
        <p>TESTING OUTSIDE BOX</p>
    <div id="filters-container-<?php echo $unique_id; ?>" style="display: none; border: 1px solid #ccc; padding: 1em; margin-top: 1em;">
        <p>TESTING INSIDE BOX</p>
        <h4 style="margin-top: 0;">Refine Your Search for <?php echo htmlspecialchars($product_category); ?></h4>
        <input type="hidden" name="k" value="<?php echo htmlspecialchars($product_category); ?>">
        <div id="search-box-<?php echo $unique_id; ?>" style="padding: 10px; border: 1px solid #ccc;">
            <?php
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
    </div>
    <?php
    echo get_search_script($unique_id, $product_category, $specialKeys);
    return ob_get_clean();
}
