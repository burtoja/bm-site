
/**
 * Constructs a 'q' string from selected category, subcategory names, and selected filter values.
 * Excludes filter group names like "Manufacturer" or "Type".
 */
function buildQueryFromSelections({ categories, selectedOptions, globalFilters }) {
    const selectedTerms = [];

    // Convert selected option IDs to a Set of strings for safe comparison
    const selectedSet = new Set(selectedOptions.map(id => String(id)));

    // Recursively collect active category/subcategory names
    function collectActiveCategories(nodes) {
        for (const node of nodes) {
            if (node.open) {
                selectedTerms.push(node.name);
                if (Array.isArray(node.subcategories)) {
                    collectActiveCategories(node.subcategories);
                }
            }
        }
    }

    function traverseFilters(node) {
        if (!node.filters) return;
        for (const filter of node.filters) {
            const selectedSet = new Set(selectedOptions.map(id => String(id)));

            for (const option of filter.options) {
                if (selectedSet.has(String(option.id))) {
                    selectedTerms.push(option.value);
                }
            }

        }
    }

    collectActiveCategories(categories);

    for (const category of categories) {
        traverseFilters(category);
        for (const subcat of category.subcategories || []) {
            traverseFilters(subcat);
            for (const subsub of subcat.subcategories || []) {
                traverseFilters(subsub);
            }
        }
    }

    if (globalFilters.condition.length > 0) {
        globalFilters.condition.forEach(val => selectedTerms.push(val));
    }

    if (globalFilters.minPrice) {
        selectedTerms.push(`min ${globalFilters.minPrice}`);
    }

    if (globalFilters.maxPrice) {
        selectedTerms.push(`max ${globalFilters.maxPrice}`);
    }

    return selectedTerms.join(' ');
}

