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
    const params = {};

    // Define default filter values to ignore when determining active categories
    const defaultIgnoreValues = {
        "Price Range": "Any",
        "Sort Order": "High to Low"
    };

    let category = null;
    let filters = null;

    // Step 1: Find the first category with meaningful (non-default) filter selections
    for (const [cat, f] of Object.entries(translatedData)) {
        const hasMeaningfulFilters = Object.keys(f).some(k => {
            const val = f[k];

            // Skip if the value is a known default (e.g., "Any", "High to Low")
            if (defaultIgnoreValues[k] && val === defaultIgnoreValues[k]) return false;

            // Allow arrays with selections (e.g., manufacturers, voltages)
            if (Array.isArray(val)) return val.length > 0;

            // Allow custom price ranges with values
            if (typeof val === "object" && val !== null) {
                return Object.values(val).some(v => v);
            }

            // Skip empty strings or nulls
            return val !== null && val !== "";
        });

        if (hasMeaningfulFilters) {
            category = cat;
            filters = f;
            break;
        }
    }

    // If no filters were selected, return an empty object
    if (!category || !filters) return params;

    // Step 2: Add the selected category as the "k" (keyword) parameter
    params.k = category;

    // Step 3: Process each filter under the selected category
    for (const [label, value] of Object.entries(filters)) {
        if (Array.isArray(value) && value.length > 0) {
            // Special handling for Manufacturer
            if (label.toLowerCase() === 'manufacturer') {
                params.manufacturer = value[0]; // Use only the first manufacturer for now
            } else {
                // Convert label to lowercase snake_case and assign values
                const key = label.toLowerCase().replace(/\s+/g, '_');
                params[key] = value;
            }
        } else if (typeof value === 'object' && value !== null && label === 'Custom Price Range') {
            // Add custom price range values if present
            if (value.min) params.min_price = value.min;
            if (value.max) params.max_price = value.max;
        } else if (label === 'Sort Order') {
            // Convert sort order text to internal API sort code
            params.sort_select = (value === 'Low to High') ? 'price_asc' : 'price_desc';
        } else if (label === 'Condition' && value !== 'Any') {
            params.condition = value;
        } else if (label === 'Price Range' && value !== 'Any') {
            params.price_range = value;
        }
    }


    return params;
}

// Expose the function globally so it can be used in other script files
window.extractSearchParameters = extractSearchParameters;
