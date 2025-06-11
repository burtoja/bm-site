
/**
 * Constructs a 'q' string from selected category, subcategory names, and selected filter values.
 * Excludes filter group names like "Manufacturer" or "Type".
 */
function buildQueryFromSelections({ categories, selectedOptions, globalFilters }) {
    const selectedTerms = [];

    // Helper: Recursively collect selected category/subcategory names
    function collectActiveCategories(nodes) {
        for (const node of nodes) {
            if (node.open) {
                selectedTerms.push(node.name);

                // Dive deeper into subcategories if open
                if (Array.isArray(node.subcategories) && node.subcategories.length > 0) {
                    collectActiveCategories(node.subcategories);
                }
            }
        }
    }

    // Collect names of open (active) categories/subcategories
    collectActiveCategories(categories);

    // Filter option text values (e.g. "Briggs & Stratton")
    for (const category of categories) {
        traverseFilters(category);
        for (const subcat of category.subcategories || []) {
            traverseFilters(subcat);
            for (const subsub of subcat.subcategories || []) {
                traverseFilters(subsub);
            }
        }
    }

    function traverseFilters(node) {
        if (!node.filters) {
            console.warn("No filters in node:", node.name);
            return;
        }

        for (const filter of node.filters) {
            if (!Array.isArray(filter.options)) {
                console.warn("Filter has no options:", filter.name);
                continue;
            }

            for (const option of filter.options) {
                const id = String(option.id);
                if (selectedOptions.includes(id)) {
                    console.log(`Matched option: ${id} ->`, option.value);
                    selectedTerms.push(option.value);
                }
            }
        }
    }


    // Global filters
    if (globalFilters.condition.length > 0) {
        globalFilters.condition.forEach(val => selectedTerms.push(val));
    }

    if (globalFilters.minPrice) {
        selectedTerms.push(`min ${globalFilters.minPrice}`);
    }

    if (globalFilters.maxPrice) {
        selectedTerms.push(`max ${globalFilters.maxPrice}`);
    }

    // Sort order is not part of q
    // Return combined q string
    return selectedTerms.join(' ');
}
