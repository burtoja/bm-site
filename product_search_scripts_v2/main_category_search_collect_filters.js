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

    // Look for the selected category-toggle, then its parent .category-item
    const selectedToggle = document.querySelector('.category-toggle.selected');
    const selectedCategoryEl = selectedToggle?.closest('.category-item');

    if (!selectedCategoryEl) {
        console.warn("⚠️ No selected category found.");
        return data;
    }

    const categoryName = selectedToggle.textContent.replace('[+]', '').replace('[-]', '').trim();
    if (!categoryName) {
        console.warn("⚠️ No category name found in toggle.");
        return data;
    }

    const categoryData = {};

    // Collect checkboxes
    selectedCategoryEl.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
        const name = cb.name.replace(/\[\]$/, '');
        if (!categoryData[name]) categoryData[name] = [];
        categoryData[name].push(cb.value);
    });

    // Collect radio buttons
    selectedCategoryEl.querySelectorAll('input[type="radio"]:checked').forEach(rb => {
        categoryData[rb.name] = rb.value;
    });

    // Custom price inputs
    const minPriceInput = selectedCategoryEl.querySelector('input[name^="min_price_"]');
    const maxPriceInput = selectedCategoryEl.querySelector('input[name^="max_price_"]');
    if ((minPriceInput && minPriceInput.value) || (maxPriceInput && maxPriceInput.value)) {
        categoryData['custom_price'] = {
            min: minPriceInput?.value || null,
            max: maxPriceInput?.value || null
        };
    }

    // Always include this category, even if no subfilters are selected
    data[categoryName] = categoryData;

    return data;
}

