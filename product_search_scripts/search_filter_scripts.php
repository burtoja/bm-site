<?php
// search_filter_scripts.php

/**
 * Function to bring over the scripts that make the search filter form do its thing
 *
 * @param $unique_id
 * @param $product_category
 * @param $specialKeys
 * @return false|string
 */

function get_search_script($unique_id, $product_category, $specialKeys) {
    ob_start();
    ?>
    <script>
        const product_category_<?php echo $unique_id; ?> = '<?php echo esc_js($product_category); ?>';
        const specialFilterKeys_<?php echo $unique_id; ?> = <?php echo json_encode($specialKeys); ?>;

        // Execute action when Find Products button clicked
        document.addEventListener("DOMContentLoaded", function() {
            let btn = document.getElementById('find-products-button-<?php echo $unique_id; ?>');
            if (btn) {
                btn.addEventListener('click', function () {
                    const condition = document.querySelector('input[name="condition-<?php echo $unique_id; ?>"]:checked')?.value || '';

                    // Manufacturer, Type, Additional Filters
                    const manufacturer = document.getElementById('manufacturer-<?php echo $unique_id; ?>')?.value || '';
                    const type = document.getElementById('type-<?php echo $unique_id; ?>')?.value || '';
                    const additionalFilters = document.getElementById('additional-filters-<?php echo $unique_id; ?>')?.value.trim() || '';

                    // Price Range
                    const priceRangeOption = document.querySelector('input[name="price_range_option-<?php echo $unique_id; ?>"]:checked')?.value || '';
                    let minPrice = '', maxPrice = '';
                    if (priceRangeOption === 'under100') {
                        minPrice = '0';
                        maxPrice = '100';
                    } else if (priceRangeOption === 'anyPrice') {
                        minPrice = '';
                        maxPrice = '';
                    } else {
                        minPrice = document.getElementById('min_price-<?php echo $unique_id; ?>')?.value.trim() || '';
                        maxPrice = document.getElementById('max_price-<?php echo $unique_id; ?>')?.value.trim() || '';
                    }

                    // Sort
                    const sortValue = document.getElementById('sort_select-<?php echo $unique_id; ?>')?.value || '';

                    // Construct URLSearchParams
                    const params = new URLSearchParams();
                    if (product_category_<?php echo $unique_id; ?>.indexOf('_') === -1) {
                        params.append('k', product_category_<?php echo $unique_id; ?>);
                    } else {
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
                    window.location.href = targetURL; // Open in same tab
                    //window.open(targetURL, '_blank');  //open in new tab
                });
            } else {
                console.error("Find Products button not found! ID:", 'find-products-button-<?php echo $unique_id; ?>');
            }
        });
    </script>
    <?php
    echo custom_price_entry_listener($unique_id);
    echo custom_manufacturer_list_listener($product_category, $unique_id);
    return ob_get_clean();
}


/**
 * Listener to switch to "Custom Range" radio if the user types into the Min/Max fields
 *
 * @param $unique_id
 * @return false|string
 */
function custom_price_entry_listener($unique_id) {
    ob_start();
    ?>
    <script>
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
    </script>
    <?php
    return ob_get_clean();
}


/**
 * Listener to switch to alternate manufacturer list in pull down
 * if one is provided for a specific product type
 *
 * @param $product_category
 * @param $unique_id
 * @return false|string
 */
function custom_manufacturer_list_listener($product_category, $unique_id) {
    ob_start();
    ?>
    <script>
    // Listen for Type changes, populate Manufacturer dynamically
    document.getElementById('type-<?php echo $unique_id; ?>').addEventListener('change', function() {
        const selectedType = this.value; // e.g. "submersible"
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
                // If you have lines with <override> in them, parse them here if needed
                // Otherwise just create an <option>
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
    </script>
    <?php
    return ob_get_clean();
}




?>


