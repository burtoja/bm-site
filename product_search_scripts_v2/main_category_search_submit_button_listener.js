/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
function waitForFormAndAttachListener(retries = 20) {
    const form = document.getElementById("product-filter-form");

    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const filterData = collectMainCategoryFilters();

            // Send alert if no categories selected
            if (!filterData || Object.keys(filterData).length === 0) {
                alert("\u26a0\ufe0f Please select a product category before searching.");
                return;
            }

            try {
                const translatedFilters = await fetch('/product_search_scripts_v2/translate_filters.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filters: filterData })
                }).then(res => res.json());
                console.log("Translated filters:", translatedFilters);

                // Build query string
                const queryString = buildQueryStringFromSearchParams(translatedFilters);
                console.log("Built query string:", queryString);

                // Parse it into URLSearchParams
                const params = new URLSearchParams(queryString);

                // If 'k' exists, replace it with 'q'
                if (params.has('k')) {
                    params.set('q', params.get('k'));
                    params.delete('k');
                }

                // Make the final API URL
                const apiUrl = '/product_search_scripts_v2/search_ebay.php?' + params.toString();
                console.log("Proxy API URL:", apiUrl);

                // Fetch data
                const data = await fetch(apiUrl).then(res => res.json());

                if (data) {
                    console.log("\u2705 search_ebay.php returned data");
                    renderResults(data);

                    // Auto scroll to new results if needed
                    document.getElementById('search-results').scrollIntoView({ behavior: 'smooth' });
                } else {
                    document.getElementById('search-results').innerHTML = '<p>No results found.</p>';
                }
            } catch (err) {
                console.error("Error translating filters or building endpoint:", err);
                document.getElementById('search-results').innerHTML = '<p>Error processing search.</p>';
            }
        });

    } else if (retries > 0) {
        console.log("Waiting for #product-filter-form...");
        setTimeout(() => waitForFormAndAttachListener(retries - 1), 100);
    } else {
        console.error("Could not find identifiable element: #product-filter-form (after retrying)");
    }
}

// Start waiting as soon as JS runs
document.addEventListener("DOMContentLoaded", waitForFormAndAttachListener);
