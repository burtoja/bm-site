/**
 * Extracts selected filters from translatedData and converts them into
 * a flat set of parameters to build a query string or API call.
 *
 * @param {Object} translatedData - The object returned from translate_filters.php
 * @returns {Object} params - Cleaned and flattened search parameters
 */
function extractSearchParameters(translatedData) {
    console.log("Incoming translatedData to extractSearchParameters():", translatedData);

    const params = {};       // Final search parameters to be returned
    const miscFilters = [];  // Any filter values not explicitly handled go here

    // Filters with these values are considered unselected/default
    const defaultIgnoreValues = {
        "Price Range": "Any",
        "Sort Order": "High to Low"
    };

    // Recognized normalized field names that should not be treated as misc
    const knownNormalizedFields = [
        'manufacturer',
        'price_range',
        'condition',
        'sort_order',
        'custom_price_range'
    ];

    let category = null;
    let filters = null;

    // Step 1: Get the first category in translatedData
    for (const [cat, f] of Object.entries(translatedData)) {
        category = cat;
        filters = f;
        break;
    }

    if (!category) {
        console.log("No category selected.");
        return params; // Exit early if there's nothing to process
    }

    // Step 2: Add the main category to the q parameter
    params.k = category;

    // Step 3: Process each filter within the selected category
    for (const [rawName, value] of Object.entries(filters)) {

        // Normalize display-style and underscored filter names to a common set
        const normalizedMap = {
            'Sort Order': 'sort_order',
            'sort_order': 'sort_order',
            'Price Range': 'price_range',
            'price_range': 'price_range',
            'Condition': 'condition',
            'condition': 'condition',
            'Type': 'type',
            'type': 'type',
            'Manufacturer': 'manufacturer',
            'manufacturer': 'manufacturer',
        };

        let normalizedName = normalizedMap[rawName] || rawName;

// Additional prefix-based normalization
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

        //let normalizedName = rawName;
        // // Normalize common prefixes to generic filter names (remove the trailing id # from them)
        // if (rawName.startsWith('condition_')) {
        //     normalizedName = 'condition';
        // } else if (rawName.startsWith('price_range_')) {
        //     normalizedName = 'price_range';
        // } else if (rawName.startsWith('sort_order_')) {
        //     normalizedName = 'sort_order';
        // } else if (rawName.startsWith('manufacturer')) {
        //     normalizedName = 'manufacturer';
        // } else if (rawName.startsWith('min_price_') || rawName.startsWith('max_price_')) {
        //     normalizedName = 'custom_price_range';
        // }

        // Handle known filters with special behavior
        if (normalizedName === 'sort_order') {
            // Convert user-friendly value to eBay sort param
            params.sort = (value === 'Low to High') ? 'price' : '-price';
            console.log("The value of parms.sort is: " + params.sort);
            continue;
        }

        if (normalizedName === 'condition') {
            // Only add condition if it's not the default
            if (value !== 'Any') {
                params.condition = value;
            }
            continue;
        }

        // Handle general known filters
        if (knownNormalizedFields.includes(normalizedName)) {
            if (Array.isArray(value) && value.length > 0) {
                if (normalizedName === 'manufacturer') {
                    params.manufacturer = value[0]; // Just use first manufacturer for now
                } else {
                    params[normalizedName] = value;
                }
            } else if (
                typeof value === 'object' &&
                value !== null &&
                normalizedName === 'custom_price_range'
            ) {
                // For custom price range, use explicit min/max values
                if (value.min) params.min_price = value.min;
                if (value.max) params.max_price = value.max;
            }

            continue;
        }

        // Catch-all for miscellaneous filters
        console.warn(`Filter "${rawName}" (normalized as "${normalizedName}") treated as misc.`);
        if (Array.isArray(value)) {
            value.forEach(v => {
                if (v && v.trim() && v !== 'Any' && v !== 'High to Low') {
                    miscFilters.push(v.trim());
                }
            });
        } else if (
            typeof value === 'string' &&
            value.trim() !== '' &&
            value !== 'Any' &&
            value !== 'high_to_low'
        ) {
            miscFilters.push(value.trim());
        }
    }

    // Step 4: Attach misc filters to the final parameter object if any
    if (miscFilters.length > 0) {
        params.misc_filters = miscFilters;
    }

    // Debug output for clarity
    console.log("Misc Filters collected:", miscFilters);
    console.log("Final extracted search params:", params);

    return params;
}

// Make this function globally accessible to other scripts on the page
window.extractSearchParameters = extractSearchParameters;
