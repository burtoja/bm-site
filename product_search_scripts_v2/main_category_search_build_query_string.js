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
    const params = extractSearchParameters(filterData);
    const urlParams = new URLSearchParams();

    if (!params.k) {
        console.warn("No category selected or no keyword (k) found");
        return '';
    }

    urlParams.set('k', params.k);

    if (params.manufacturer) urlParams.append('manufacturer', params.manufacturer);
    if (params.condition) urlParams.append('condition', params.condition);
    if (params.price_range) urlParams.append('price_range', params.price_range);
    if (params.min_price) urlParams.append('min_price', params.min_price);
    if (params.max_price) urlParams.append('max_price', params.max_price);
    if (params.sort_select) urlParams.append('sort_select', params.sort_select);

    // 🛠️ NEW: If we have misc_filters collected, add them individually
    if (params.misc_filters && Array.isArray(params.misc_filters)) {
        params.misc_filters.forEach(term => {
            urlParams.append('misc', term);
        });
    }

    console.log("🔧 Final Built Query String:", urlParams.toString());
    return urlParams.toString();
}
