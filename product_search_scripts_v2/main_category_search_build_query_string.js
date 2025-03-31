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

/**
 * Converts structured category filter data into a URL query string.
 *
 * @param {Object} filterData - The structured filter data with category names and values
 * @returns {String} URL query string
 */
function buildQueryStringFromSearchParams(filterData) {
    const urlParams = new URLSearchParams();

    for (const [categoryName, filters] of Object.entries(filterData)) {
        const hasMeaningfulFilters = Object.entries(filters).some(([key, value]) => {
            // Skip known default values
            if (key.startsWith("condition") && value === "any") return false;
            if (key.startsWith("price_range") && value === "any") return false;
            if (key.startsWith("sort_order")) return false;

            if (key.startsWith("custom_price")) {
                return value.min || value.max;
            }

            return value && (
                (Array.isArray(value) && value.length > 0) ||
                (typeof value === 'string' && value.trim() !== '') ||
                (typeof value === 'object' && value !== null)
            );
        });

        // ✅ EVEN if no filters, use the category name for keyword
        urlParams.set('k', categoryName);

        if (hasMeaningfulFilters) {
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
        } else {
            console.warn("⚠️ No meaningful filters found — using only category.");
        }

        break; // ✅ Only use the first selected category
    }

    console.log("🔧 Built query string:", urlParams.toString());
    return urlParams.toString();
}
