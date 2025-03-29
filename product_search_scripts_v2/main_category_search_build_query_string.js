/**
 * Flatten a nested filter object into a flat key/value structure,
 * extracting the top-level category name as the search keyword (k).
 *
 * Example:
 * {
 *   "Pumps": {
 *     "manufacturer_2": ["Dayton"],
 *     "condition_2": "used",
 *     "custom_price": { min: "100", max: "500" }
 *   }
 * }
 * → k=Pumps&manufacturer_2=Dayton&condition_2=used&custom_price_min=100&custom_price_max=500
 */
function buildQueryStringFromSearchParams(filterData) {
    const urlParams = new URLSearchParams();

    // Step 1: Extract the first non-empty category as keyword
    for (const [categoryName, filters] of Object.entries(filterData)) {
        const hasFilters = Object.keys(filters).length > 0;

        if (hasFilters) {
            urlParams.set('k', categoryName); // search keyword
            // Step 2: Flatten all filters from that category
            for (const [key, value] of Object.entries(filters)) {
                if (Array.isArray(value)) {
                    value.forEach(v => urlParams.append(key, v));
                } else if (typeof value === 'object' && value !== null) {
                    for (const [subKey, subValue] of Object.entries(value)) {
                        if (subValue) {
                            urlParams.append(`${key}_${subKey}`, subValue);
                        }
                    }
                } else {
                    urlParams.append(key, value);
                }
            }
            break; // Only use the first category with active filters
        }
    }

    console.log("🔍 Final query string:", urlParams.toString());
    return urlParams.toString();
}
