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
    const knownFields = ['manufacturer', 'price_range', 'condition', 'sort_order', 'custom_price_range'];

    for (const [rawName, value] of Object.entries(filters)) {
        // Normalize the field name
        let name = rawName;

        if (knownFields.includes(name)) {
            if (rawName.startsWith('condition_')) {
                name = 'Condition';
            } else if (rawName.startsWith('price_range_')) {
                name = 'Price Range';
            } else if (rawName.startsWith('sort_order_')) {
                name = 'Sort Order';
            } else if (rawName.startsWith('min_price_') || rawName.startsWith('max_price_')) {
                name = 'Custom Price Range';
            } else if (rawName.startsWith('manufacturer')) {
                name = 'Manufacturer';
            }

            if (Array.isArray(value) && value.length > 0) {
                if (name.toLowerCase() === 'manufacturer') {
                    params.manufacturer = value[0]; // First manufacturer
                } else {
                    const key = name.toLowerCase().replace(/\s+/g, '_');
                    params[key] = value;
                }
            } else if (typeof value === 'object' && value !== null && name === 'Custom Price Range') {
                if (value.min) params.min_price = value.min;
                if (value.max) params.max_price = value.max;
            } else if (name === 'Sort Order') {
                params.sort_select = (value === 'Low to High') ? 'price_asc' : 'price_desc';
            } else if (name === 'Condition' && value !== 'Any') {
                params.condition = value;
            } else if (name === 'Price Range' && value !== 'Any') {
                params.price_range = value;
            }
        } else {
            // Collect misc filters
            if (Array.isArray(value)) {
                miscFilters.push(...value);
            } else if (typeof value === 'string' && value.trim()) {
                miscFilters.push(value);
            }
        }
        console.log("Misc Filters: " + miscFilters);
        // Attach misc filters to param array
        if (miscFilters.length > 0) {
            params.misc_filters = miscFilters;
        }
    }

    console.log("\ud83c\udf10 Extracted search parameters:", params); // <-- Debug helper!

    return params;
}

// Expose the function globally so it can be used in other script files
window.extractSearchParameters = extractSearchParameters;
