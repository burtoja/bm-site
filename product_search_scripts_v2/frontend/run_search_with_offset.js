/**
 * Runs search with offset to help pagination happen
 *
 * @param newOffset
 * @returns {Promise<void>}
 */
async function runSearchWithOffset(newOffset) {
    try {
        const form = document.getElementById("product-filter-form");
        if (!form) {
            console.error("Cannot find form to re-run search.");
            return;
        }

        const filterData = collectMainCategoryFilters();
        const translatedFilters = await fetch('/product_search_scripts_v2/translate_filters.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filters: filterData })
        }).then(res => res.json());

        const params = new URLSearchParams(buildQueryStringFromSearchParams(translatedFilters));

        if (!params.get('k')) {
            console.warn("No keyword found. Skipping API call.");
            return;
        }

        params.set('offset', newOffset); // Update the offset for the new page

        const urlParams = new URLSearchParams(params);

        // Convert 'k' to 'q'
        if (urlParams.has('k')) {
            urlParams.set('q', urlParams.get('k'));
            urlParams.delete('k');
        }

        const apiUrl = '/product_search_scripts_v2/search_ebay.php?' + urlParams.toString();
        console.log("Proxy API URL (paginated):", apiUrl);

        const data = await fetch(apiUrl).then(res => res.json());

        if (data) {
            renderResults(data);
            document.getElementById('search-results').scrollIntoView({ behavior: 'smooth' });
        } else {
            document.getElementById('search-results').innerHTML = '<p>No results found.</p>';
        }

    } catch (err) {
        console.error("Error during paginated search:", err);
    }
}
