
/**
 * Constructs a 'q' string from selected category, subcategory names, and selected filter values.
 * Excludes filter group names like "Manufacturer" or "Type".
 */

function buildQueryFromSelections({ categories, selectedOptions, globalFilters }) {
    const selectedTerms = [];

    // Normalize selectedOptions into a Set of strings for comparison
    const selectedSet = new Set(selectedOptions.map(id => String(id)));

    // Collect names of open (visible) categories and subcategories
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

    // Collect selected filter option values
    function traverseFilters(node) {
        if (!node.filters) return;
        for (const filter of node.filters) {
            for (const option of filter.options) {
                console.log('Checking option.id:', option.id, 'match?', selectedSet.has(String(option.id)));
                if (selectedSet.has(String(option.id))) {
                    console.log('Matched option:', option.value);
                    selectedTerms.push(option.value);
                }

            }
        }
    }

    // Traverse full category tree
    function collectAllSelectedFilters(nodes) {
        for (const node of nodes) {
            console.log('Traversing subcat:', subcat.name, subcat);
            traverseFilters(node);
            if (Array.isArray(node.subcategories)) {
                collectAllSelectedFilters(node.subcategories);
            }
        }
    }

    collectActiveCategories(categories);
    collectAllSelectedFilters(categories);

    // Add global filters
    if (globalFilters.condition?.length) {
        selectedTerms.push(...globalFilters.condition);
    }
    if (globalFilters.minPrice) {
        selectedTerms.push(`min ${globalFilters.minPrice}`);
    }
    if (globalFilters.maxPrice) {
        selectedTerms.push(`max ${globalFilters.maxPrice}`);
    }

    return selectedTerms.join(' ');
}
