<?php
// common_search_scripts.php
?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const uniqueId = "<?php echo $unique_id; ?>";
    const productCategory = "<?php echo esc_js($product_category); ?>";
    const specialFilterKeys = <?php echo json_encode($specialFilterKeys); ?>;
    
    console.log("Special filters for category:", specialFilterKeys);  // TESTING

    // Listen for Type changes, populate Manufacturer dynamically
    document.getElementById(`type-${uniqueId}`).addEventListener('change', function() {
        const selectedType = this.value.trim().toLowerCase().replace(/\s+/g, '_'); // slugifies type
        let fetchUrl;
        const categoryLower = "<?php echo strtolower($product_category); ?>";

        // Build the URL for get_manufacturers.php
        if (!selectedType) {    
            fetchUrl = `/product_search_scripts/get_manufacturers.php?category=${encodeURIComponent(categoryLower)}`;
        } else {
            fetchUrl = `/product_search_scripts/get_manufacturers.php?category=${encodeURIComponent(categoryLower)}&type=${encodeURIComponent(selectedType)}`;
        }

        // Fetch manufacturer list
        fetch(fetchUrl)
            .then(response => response.json())
            .then(data => {
                const mSelect = document.getElementById(`manufacturer-${uniqueId}`);
                mSelect.innerHTML = '<option value="">Select a Manufacturer (or leave blank to see all)</option>';

                data.forEach(manuf => {
                    let opt = document.createElement('option');
                    opt.value = manuf;
                    opt.textContent = manuf;
                    mSelect.appendChild(opt);
                });
            })
            .catch(err => {
                console.error("Error fetching manufacturers:", fetchUrl, err);
            });
    });

    // Switch to "Custom Range" radio if the user types into the Min/Max fields    
    document.getElementById(`min_price-${uniqueId}`).addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.querySelector(`input[name="price_range_option-${uniqueId}"][value="custom"]`).checked = true;
        }
    });

    document.getElementById(`max_price-${uniqueId}`).addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.querySelector(`input[name="price_range_option-${uniqueId}"][value="custom"]`).checked = true;
        }
    });

    document.getElementById(`find-products-button-${uniqueId}`).addEventListener('click', function () {
        // Gather all selected filter values
        const condition = document.querySelector(`input[name="condition-${uniqueId}"]:checked`).value;
        const manufacturer = document.getElementById(`manufacturer-${uniqueId}`).value;
        const type = document.getElementById(`type-${uniqueId}`).value;
        const additionalFilters = document.getElementById(`additional-filters-${uniqueId}`).value.trim();
        
        // Price Range
        const priceRangeOption = document.querySelector(`input[name="price_range_option-${uniqueId}"]:checked`).value;
        let minPrice = '', maxPrice = '';
        if (priceRangeOption === 'under100') {
            minPrice = '0';
            maxPrice = '100';
        } else if (priceRangeOption === 'anyPrice') {
            minPrice = '';
            maxPrice = '';
        } else {
            minPrice = document.getElementById(`min_price-${uniqueId}`).value.trim();
            maxPrice = document.getElementById(`max_price-${uniqueId}`).value.trim();
        }

        // Sort option
        const sortValue = document.getElementById(`sort_select-${uniqueId}`).value;

        // Construct URLSearchParams
        const params = new URLSearchParams();
        if (productCategory.indexOf('_') === -1) {
            params.append('k', productCategory);
        }
        if (condition) params.append('condition', condition);
        if (manufacturer) params.append('manufacturer', manufacturer);
        if (type) params.append('type', type);
        if (additionalFilters) params.append('filters', additionalFilters);
        if (minPrice) params.append('min_price', minPrice);
        if (maxPrice) params.append('max_price', maxPrice);
        if (sortValue) params.append('sort_select', sortValue);

        // Appending special filter parameters to URL
        specialFilterKeys.forEach(sf => {
            let elemId = `${productCategory}-special-${sf}-${uniqueId}`;
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
        window.open(targetURL, '_blank');  // Open in new tab
    });
});
</script>
