/**
 * This function will take the translated filter array and pull out only the selected values
 * Which can then be used to build the api endpoint.
 * 
 * @param translatedData
 * @returns {{}}
 */
function extractSearchParameters(translatedData) {
    const params = {};

    // Assume we only use the first category in the object
    const [category, filters] = Object.entries(translatedData)[0] || [];

    if (!category || !filters) return params;

    params.k = category;

    for (const [label, value] of Object.entries(filters)) {
        if (Array.isArray(value) && value.length > 0) {
            // special handling for known filter names
            if (label.toLowerCase() === 'manufacturer') {
                params.manufacturer = value[0]; // pick first for now
            } else {
                // lowercase the label and use as a dynamic parameter
                const key = label.toLowerCase().replace(/\s+/g, '_');
                params[key] = value;
            }
        } else if (typeof value === 'object' && value !== null && label === 'Custom Price Range') {
            if (value.min) params.min_price = value.min;
            if (value.max) params.max_price = value.max;
        } else if (label === 'Sort Order') {
            params.sort_select = (value === 'Low to High') ? 'price_asc' : 'price_desc';
        }
    }

    return params;
}
