/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
function waitForFormAndAttachListener(retries = 20) {
    const form = document.getElementById("product-filter-form");

    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const filterData = collectMainCategoryFilters();
            //console.log("Collected filter data:", filterData);

            try {
                const translatedFilters = await fetch('/product_search_scripts_v2/translate_filters.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filters: filterData })
                }).then(res => res.json());
            //console.log("Translated filters:", translatedFilters);

            const queryString = buildQueryStringFromSearchParams(translatedFilters);
            console.log("Built query string:", queryString);

            const params = new URLSearchParams(queryString);
            //console.log("Params object:", params.toString());

            //fetch the keyword parameter
            const queryStringFull = params.toString();

            if (!params.get('k')) {
                console.warn("No keyword found. Skipping API call.");
                return;
            }

            const apiUrl = '/product_search_scripts_v2/search_ebay.php?' + queryStringFull;
            //console.log("Proxy API URL (note: this is not what is sent--check search_ebay.php):", apiUrl);

            const data = await fetch(apiUrl).then(res => res.json());

            if (data) {
            renderResults(data);
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
