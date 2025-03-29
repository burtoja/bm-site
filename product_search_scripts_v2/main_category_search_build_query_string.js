/**
 * Builds a flattened query string from translated filter data,
 * selecting the first category with meaningful (non-default) filter values.
 *
 * - Uses the first category with real user selections as the eBay search keyword (k)
 * - Filters out default values like "Any" or "High to Low" unless meaningful selections are also made
 * - Supports multi-value filters (arrays), scalar values, and custom price ranges
 * - Returns a URL-encoded query string to use with URLSearchParams or eBay API calls
 *
 * @param {Object} filterData - Translated filter object grouped by category name
 * @returns {string} Query string (e.g., k=Pumps&Manufacturer=Dayton&Manufacturer=GE)
 */

function buildQueryStringFromSearchParams(filterData) {
    const urlParams = new URLSearchParams();

    let selectedCategoryName = null;
    let selectedFilters = {};

    // STEP 1: Find first category with meaningful filters (ignoring default-only filters)
    for (const [categoryName, filters] of Object.entries(filterData)) {
        const hasMeaningfulFilter = Object.entries(filters).some(([key, value]) => {
            return isMeaningfulFilter(key, value);
        });

        if (hasMeaningfulFilter) {
            selectedCategoryName = categoryName;
            selectedFilters = filters;
            break;
        }
    }

    if (!selectedCategoryName) {
        console.warn("⚠️ No meaningful filters found — using no category.");
        return urlParams.toString(); // Empty or fallback string
    }

    // STEP 2: Set the keyword
    urlParams.set('k', selectedCategoryName);

    // STEP 3: Add filters (including default values now that we have the right category)
    for (const [key, value] of Object.entries(selectedFilters)) {
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

    console.log("🔍 Final query string:", urlParams.toString());
    return urlParams.toString();
}


// ✅ Helper: What counts as a meaningful user selection
function isMeaningfulFilter(key, value) {
    const isDefault = (
        (key.toLowerCase().includes("condition") && value === "Any") ||
        (key.toLowerCase().includes("price range") && value === "Any") ||
        (key.toLowerCase().includes("sort order")) ||
        (key.toLowerCase().includes("custom price") && !value.min && !value.max)
    );

    const isEmpty = (
        value === null ||
        value === "" ||
        (Array.isArray(value) && value.length === 0)
    );

    return !isDefault && !isEmpty;
}
