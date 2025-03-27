/**
 * Collect all selected filter values from the main category search interface.
 *
 * This function loops through each category block in the filter form,
 * and builds a structured object that includes:
 *  - All checked checkboxes (grouped by filter name)
 *  - All selected radio buttons
 *  - Any custom price inputs (min and/or max)
 *
 * @returns {Object} A structured object mapping each category to its selected filter values.
 */
function collectMainCategoryFilters() {
    const data = {};

    // Loop over each product category block on the page
    document.querySelectorAll('.category-item').forEach(categoryEl => {
        // Get the category name from the toggle text (e.g., "Pumps")
        const categoryToggle = categoryEl.querySelector('.category-toggle');
        if (!categoryToggle) return;

        const categoryName = categoryToggle.textContent
            .replace('[+]', '')
            .replace('[-]', '')
            .trim();
        if (!categoryName) return;

        const categoryData = {};

        // Collect all checked checkboxes under this category
        categoryEl.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const name = cb.name.replace(/\[\]$/, ''); // Normalize name (remove trailing [])
            if (!categoryData[name]) categoryData[name] = [];
            categoryData[name].push(cb.value);
        });

        // Collect selected radio buttons under this category
        categoryEl.querySelectorAll('input[type="radio"]:checked').forEach(rb => {
            categoryData[rb.name] = rb.value;
        });

        // Collect custom price inputs if provided
        const minPriceInput = categoryEl.querySelector('input[name^="min_price_"]');
        const maxPriceInput = categoryEl.querySelector('input[name^="max_price_"]');

        if ((minPriceInput && minPriceInput.value) || (maxPriceInput && maxPriceInput.value)) {
            categoryData['custom_price'] = {
                min: minPriceInput?.value || null,
                max: maxPriceInput?.value || null
            };
        }

        // Add this category's selected filters to the final data object
        data[categoryName] = categoryData;
    });

    return data;
}
