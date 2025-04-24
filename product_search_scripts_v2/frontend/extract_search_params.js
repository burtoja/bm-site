/**
 * Extracts selected filters from translatedData and converts them into
 * a flat set of parameters to be used in building a query string or API call.
 *
 * Only the first category with user-selected filters will be processed.
 * Default values like "Any" for Price Range and "High to Low" for Sort Order
 * are ignored when determining which category is active.
 *
 * @param {Object} translatedData - The object returned from translate_filters.php
 * @returns {Object} params - Cleaned and flattened search parameters
 */
function extractSearchParameters(translatedData) {
    console.log("Incoming translatedData:", translatedData);
    const params = {};
    const miscFilters = [];

    // Define default filter values to ignore when determining active categories
    const defaultIgnoreValues = {
        "Price Range": "Any",
        "Sort Order": "High to Low"
    };

    let category = null;
    let filters = null;

    // Step 1: Find the first category with meaningful (non-default) filter selections
    for (const [cat, f] of Object.entries(translatedData)) {
        category = cat;
        filters = f;
        break; // Take the first category provided in the translatedData which has one category
    }

    // If no category selected at all, return empty object
    if (!category) {
        console.log("No category selected.");
        return params;
    }

    // Step 2: Add the selected category as the "k" (keyword) parameter
    params.k = category;

    // Step 3: Process each filter under the selected category
    const knownFields = ['manufacturer', 'price_range', 'condition', 'sort_order', 'custom_price_range'];

    for (const [rawName, value] of Object.entries(filters)) {
        let normalizedName = rawName;

        // Normalize the field name
        if (rawName.startsWith('condition_')) {
            normalizedName = 'condition';
        } else if (rawName.startsWith('price_range_')) {
            normalizedName = 'price_range';
        } else if (rawName.startsWith('sort_order_')) {
            normalizedName = 'sort_order';
        } else if (rawName.startsWith('manufacturer')) {
            normalizedName = 'manufacturer';
        } else if (rawName.startsWith('min_price_') || rawName.startsWith('max_price_')) {
            normalizedName = 'custom_price_range';
        }

        // Special handling for sort_order first
        if (normalizedName === 'sort_order') {
            params.sort = (value === 'Low to High') ? 'price' : '-price';
            continue;
        }

        // Special handling for condition
        if (normalizedName === 'condition' && value !== 'Any') {
            params.condition = value;
            continue;
        }
        
        if (knownFields.includes(normalizedName)) {
            if (Array.isArray(value) && value.length > 0) {
                if (normalizedName === 'manufacturer') {
                    params.manufacturer = value[0];
                } else {
                    params[normalizedName] = value;
                }
            } else if (typeof value === 'object' && value !== null && normalizedName === 'custom_price_range') {
                if (value.min) params.min_price = value.min;
                if (value.max) params.max_price = value.max;
            }
        } else {
            // Everything else is misc
            if (Array.isArray(value)) {
                value.forEach(v => {
                    if (v && v.trim() && v !== 'Any' && v !== 'High to Low') {
                        miscFilters.push(v.trim());
                    }
                });
            } else if (typeof value === 'string' && value.trim() !== '' && value !== 'Any' && value !== 'High to Low') {
                miscFilters.push(value.trim());
            }
        }
    }

    console.log("Misc Filters: " + miscFilters);

    // Attach misc filters to param array
    if (miscFilters.length > 0) {
        params.misc_filters = miscFilters;
    }

    console.log("Misc and Param filters completed!!!");
    console.log("Misc Filters collected:", miscFilters);
    console.log("Final extracted search params:", params);

    return params;
}

// Expose the function globally so it can be used in other script files
window.extractSearchParameters = extractSearchParameters;
