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

<script>
	const product_category_<?php echo $unique_id; ?> = '<?php echo esc_js($product_category); ?>';
	<?php include include ($_SERVER["DOCUMENT_ROOT"] . '/product_search_scripts/general_form_element_bottom_scripts.js') ?>
	const specialFilterKeys_<?php echo $unique_id; ?> = <?php echo json_encode($specialFilterKeys); ?>;
  	console.log("Special filters for category:", specialFilterKeys_<?php echo $unique_id; ?>);  //TESTING

// Listen for Type changes, populate Manufacturer dynamically
    document.getElementById('type-<?php echo $unique_id; ?>').addEventListener('change', function() {
		const selectedType = this.value.trim().toLowerCase().replace(/\s+/g, '_'); //slugifies the type to underscores
		let fetchUrl;
		const categoryLower = '<?php echo strtolower($product_category); ?>'; 

		// Build the URL for get_manufacturers.php
		if (!selectedType) {	
			// No type selected - revert to original list
			fetchUrl = `/product_search_scripts/get_manufacturers.php?category=${encodeURIComponent(categoryLower)}`;
		} else {
			// Type selected - load specific file
			fetchUrl = `/product_search_scripts/get_manufacturers.php?category=${encodeURIComponent(categoryLower)}&type=${encodeURIComponent(selectedType)}`;
		}

		// Fetch from the endpoint
		fetch(fetchUrl)
			.then(response => response.json())
			.then(data => {
				// data should be an array of strings from the text file
				const mSelect = document.getElementById('manufacturer-<?php echo $unique_id; ?>');
				mSelect.innerHTML = '<option value="">Select a Manufacturer (or leave blank to see all)</option>';

				data.forEach(manuf => {
					// If lines with <override> in them, parse them or just create an <option>
					let opt = document.createElement('option');
					opt.value = manuf;
					opt.textContent = manuf;
					mSelect.appendChild(opt);
				});
			})
			.catch(err => {
				console.error("Error fetching manufacturers:" + fetchUrl + " -- ", err);
			});
	});
		
	// Switch to "Custom Range" radio if the user types into the Min/Max fields	
	document.getElementById('min_price-<?php echo $unique_id; ?>').addEventListener('input', function() {
		if (this.value.trim() !== '') {
			document.querySelector('input[name="price_range_option<?php echo '-' . $unique_id; ?>"][value="custom"]').checked = true;
		}
	});

	document.getElementById('max_price-<?php echo $unique_id; ?>').addEventListener('input', function() {
		if (this.value.trim() !== '') {
			document.querySelector('input[name="price_range_option<?php echo '-' . $unique_id; ?>"][value="custom"]').checked = true;
		}
	});
	
	document.getElementById('find-products-button-<?php echo $unique_id; ?>').addEventListener('click', function () {
            // Condition
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
			window.open(targetURL, '_blank');  //open in new tab    
			//window.location.href = targetURL;  //open in same tab
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('product_search_form', 'advanced_product_search_form_shortcode');
