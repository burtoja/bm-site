/**
 * Converts an object of search parameters into a properly formatted query string.
 *
 * @param {Object} searchParams - Flat object with key-value pairs or arrays
 * @returns {String} query string (e.g. k=Switchgear&voltage=240V&voltage=480V)
 */
function buildQueryStringFromSearchParams(searchParams) {
    const urlParams = new URLSearchParams();

    for (const [key, value] of Object.entries(searchParams)) {
        if (Array.isArray(value)) {
            value.forEach(v => urlParams.append(key, v));
        } else {
            urlParams.append(key, value);
        }
    }

    return urlParams.toString();
}
