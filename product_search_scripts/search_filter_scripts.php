<?php
// search_filter_scripts.php

function get_search_script($unique_id, $product_category, $specialKeys) {
    ob_start();
    ?>
    <script>
        const product_category_<?php echo $unique_id; ?> = '<?php echo esc_js($product_category); ?>';
        const specialFilterKeys_<?php echo $unique_id; ?> = <?php echo json_encode($specialKeys); ?>;

        (function() {
            // Toggle collapsible container
            var btn = document.getElementById('toggle-filters-<?php echo $unique_id; ?>');
            var box = document.getElementById('filters-container-<?php echo $unique_id; ?>');
            btn.addEventListener('click', function() {
                box.style.display = (box.style.display === 'none') ? 'block' : 'none';
            });

            console.log("Special filters for <?php echo $unique_id; ?>:", specialFilterKeys_<?php echo $unique_id; ?>);
        })();

        // Execute action when Find Products button clicked
        document.getElementById('find-products-button-<?php echo $unique_id; ?>').addEventListener('click', function () {
            console.log("BUTTON CLICKED");
            const condition = document.querySelector('input[name="condition-<?php echo $unique_id; ?>"]:checked')?.value || '';

            // Manufacturer, Type, Additional Filters
            const manufacturer = document.getElementById('manufacturer-<?php echo $unique_id; ?>')?.value || '';
            const type = document.getElementById('type-<?php echo $unique_id; ?>')?.value || '';
            const additionalFilters = document.getElementById('additional-filters-<?php echo $unique_id; ?>')?.value.trim() || '';

            // Price Range
            const priceRangeOption = document.querySelector('input[name="price_range_option-<?php echo $unique_id; ?>"]:checked')?.value || '';
            let minPrice = '', maxPrice = '';
            if (priceRangeOption === 'under100') {
                minPrice = '0'; maxPrice = '100';
            } else if (priceRangeOption === 'anyPrice') {
                minPrice = ''; maxPrice = '';
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
        });
    </script>
    <?php
    return ob_get_clean();
}
?>
