<?php
// collapsible_filter_box.php

// Include any helper files your form elements need:
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/list_handler.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_functions.php');
include_once($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/special_case_form_element_functions.php');

$product_category = isset($_GET['k']) ? $_GET['k'] : '';

/**
 * Displays a collapsible, pre-populated version of your existing search box.
 * 
 * @param string $product_category   e.g. 'industrial' or 'pumps' or 'cooling_towers'
 * @param string $unique_id          optional unique ID for multiple instances
 */
function displayCollapsibleFilterBox($product_category, $unique_id = 'collapsible1', $selectedSpecial = []) {
    
    $condition         = isset($_GET['condition'])       ? $_GET['condition']       : '';
    $manufacturer      = isset($_GET['manufacturer'])    ? trim($_GET['manufacturer'])    : '';
    $typeValue         = isset($_GET['type'])            ? trim($_GET['type'])            : '';
    $additionalFilters = isset($_GET['filters'])         ? trim($_GET['filters'])         : '';
    $sortValue         = isset($_GET['sort_select'])     ? $_GET['sort_select']     : 'price_desc';
    $priceRangeOption  = isset($_GET['price_range_option']) ? $_GET['price_range_option'] : 'anyPrice';
    $minPrice          = isset($_GET['min_price'])          ? $_GET['min_price']          : '';
    $maxPrice          = isset($_GET['max_price'])          ? $_GET['max_price']          : '';

    
    // Get special filter keys and HTML and put in array
    //$selectedSpecial = []; 
    $specialReturn = add_special_filter_elements($product_category, $unique_id, $selectedSpecial);
    $specialFilterHTML   = $specialReturn['html'];
    $specialKeys   = $specialReturn['keys']; 
    error_log("SPECIAL RETURN: " . print_r($specialReturn, true));
    
    // Pre-select itmes selected by user during search leading to this page


    // Start output buffering to return a string
    ob_start();
    ?>
    <div style="margin-bottom: 1em;">
        <!-- A button to toggle showing/hiding the filter box -->
        <button type="button" id="toggle-filters-<?php echo $unique_id; ?>"
            style="padding: 0.5em 1em; font-size: 1em; cursor: pointer;">
            Show/Hide Search Filters
        </button>

        <!-- Collapsible container, initially hidden -->
        <div id="filters-container-<?php echo $unique_id; ?>" style="display: none; border: 1px solid #ccc; padding: 1em; margin-top: 1em;">
            <h4 style="margin-top: 0;">Refine Your Search for <?php echo htmlspecialchars($product_category); ?></h4>
                <!-- Include the product category if needed -->
                <input type="hidden" name="k" value="<?php echo htmlspecialchars($product_category); ?>">
            
            
            <div id="search-box-<?php echo $unique_id; ?>" style="padding: 10px; border: 1px solid #ccc;">
            <?php
                echo add_condition_element($product_category, $unique_id, $condition);
                echo add_type_element($product_category, $unique_id, $typeValue);
                echo add_manufacturer_element($product_category, $unique_id, $manufacturer);
                echo $specialFilterHTML;  //already built and stored in this variable
                echo add_search_box_element($unique_id, $additionalFilters);
                echo add_price_filter_elements($unique_id, $priceRangeOption, $minPrice, $maxPrice);
                echo add_sort_by_element($unique_id, $sortValue);
                echo add_search_button($unique_id, $product_category);
            ?>
            </div>
        </div>
    </div>

    <script>
    const product_category_<?php echo $unique_id; ?> = '<?php echo esc_js($product_category); ?>';
	<?php include include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_bottom_scripts.js') ?>
	const specialFilterKeys_<?php echo $unique_id; ?> = <?php echo json_encode($specialKeys); ?>;
	
    (function() {
        // Toggle collapsible container
        var btn = document.getElementById('toggle-filters-<?php echo $unique_id; ?>');
        var box = document.getElementById('filters-container-<?php echo $unique_id; ?>');
        btn.addEventListener('click', function() {
            if (box.style.display === 'none') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        });

        console.log("Special filters for <?php echo $unique_id; ?>:", specialFilterKeys_<?php echo $unique_id; ?>);

        // If your add_search_button() script is building the final URL in JS,
        // you can use specialFilterKeys_<?php echo $unique_id; ?> there 
        // (For instance in the "find-products-button-<?php echo $unique_id; ?>" click).
        // You might do so by referencing window.specialFilterKeys_<?php echo $unique_id; ?> 
        // or directly if in the same scope.
    })();
    
    // Execute action when Find Products button clicked
    document.getElementById('find-products-button-<?php echo $unique_id; ?>').addEventListener('click', function () {
        console.log("BUTTON CLICKED");
        const condition = document.querySelector('input[name="condition-<?php echo $unique_id; ?>"]:checked').value;

        // Manufacturer, Type, Additional Filters
        const manufacturer = document.getElementById('manufacturer-<?php echo $unique_id; ?>').value;
        const type = document.getElementById('type-<?php echo $unique_id; ?>').value;
        const additionalFilters = document.getElementById('additional-filters-<?php echo $unique_id; ?>').value.trim();

        // Price Range
        const priceRangeOption = document.querySelector('input[name="price_range_option<?php echo '-' . $unique_id; ?>"]:checked').value;
        let minPrice = '';
        let maxPrice = '';
        if (priceRangeOption === 'under100') {
            minPrice = '0';
            maxPrice = '100';
		} else if (priceRangeOption === 'anyPrice') {
			minPrice = '';
            maxPrice = '';
		} else {
            // Use user-input values
            minPrice = document.getElementById('min_price-<?php echo $unique_id; ?>').value.trim();
            maxPrice = document.getElementById('max_price-<?php echo $unique_id; ?>').value.trim();
        }

        // Sort
        const sortValue = document.getElementById('sort_select-<?php echo $unique_id; ?>').value;

        // Construct URLSearchParams
        const params = new URLSearchParams();
					
		//option for switching underscores to plus signs
		//params.append('k', product_category_<? //php echo $unique_id; ?>.replace(/_/g, '+'));
		
		// If product_category has NO underscores, then append it as 'k'
		if (product_category_<?php echo $unique_id; ?>.indexOf('_') === -1) {
		  params.append('k', product_category_<?php echo $unique_id; ?>);
		}
        if (condition) params.append('condition', condition);
        if (manufacturer) params.append('manufacturer', manufacturer);
        if (type) params.append('type', type);
        if (additionalFilters) params.append('filters', additionalFilters); 
        if (minPrice) params.append('min_price', minPrice); 				
        if (maxPrice) params.append('max_price', maxPrice);  				
        if (sortValue) params.append('sort_select', sortValue);
	
		// Appending special filter parameters to URL
		specialFilterKeys_<?php echo $unique_id; ?>.forEach(sf => {
		  // ID pattern: {product_category}-special-{sf}-{unique_id}
		  let elemId = '<?php echo $product_category; ?>' + '-special-' + sf + '-<?php echo $unique_id; ?>';
		  let elem = document.getElementById(elemId);
		  if (elem) {
			let val = elem.value.trim();
			if (val) {
			  params.append(sf, val);
			}
		  }
		});

        // Redirect to results page
        const targetURL = `https://boilersandmachinery.com/product-listings/?${params.toString()}`;	
        console.log("Target URL:", targetURL);
		//window.open(targetURL, '_blank');  //open in new tab    
		window.location.href = targetURL;  //open in same tab
    });
    </script>
    <?php
    return ob_get_clean();
}

?>
