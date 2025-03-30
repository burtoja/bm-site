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

    // Find only the selected category
    const selectedCategoryEl = document.querySelector('.category-item .category-toggle.selected');

    if (!selectedCategoryEl) {
        console.warn("No category selected.");
        return data;
    }

    const categoryEl = selectedCategoryEl.closest('.category-item');

    // Extract category name
    const categoryName = selectedCategoryEl.textContent
        .replace('[+]', '')
        .replace('[-]', '')
        .trim();

    if (!categoryName) return data;

    const categoryData = {};

    // Collect all checked checkboxes
    categoryEl.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
        const name = cb.name.replace(/\[\]$/, '');
        if (!categoryData[name]) categoryData[name] = [];
        categoryData[name].push(cb.value);
    });

    // Collect selected radio buttons
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

    // Always include the category name in the data structure
    data[categoryName] = categoryData;

    return data;
}


