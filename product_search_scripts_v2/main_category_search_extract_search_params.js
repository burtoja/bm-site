/**
 * This function will take the translated filter array and pull out only the selected values
 * Which can then be used to build the api endpoint.
 * 
 * @param translatedData
 * @returns {{}}
 */
window.extractSearchParameters = extractSearchParameters;
function extractSearchParameters(translatedData) {
    const params = {};

    // Step 1: Find the first category with actual filters
    let category = null;
    let filters = null;

    for (const [cat, f] of Object.entries(translatedData)) {
        const nonEmptyKeys = Object.keys(f).filter(k => {
            const val = f[k];
            if (Array.isArray(val)) return val.length > 0;
            if (typeof val === "object" && val !== null) return Object.values(val).some(v => v);
            return val !== null && val !== "" && val !== "any"; // ignore unfiltered
        });

        if (nonEmptyKeys.length > 0) {
            category = cat;
            filters = f;
            break;
        }
    }

    if (!category || !filters) return params;

    params.k = category;

    for (const [label, value] of Object.entries(filters)) {
        console.log("⏳ Processing:", label, value);

        if (Array.isArray(value) && value.length > 0) {
            if (label.toLowerCase() === 'manufacturer') {
                params.manufacturer = value[0]; // just one for now
            } else {
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

    console.log("✅ Final searchParams:", params);
    return params;
}

