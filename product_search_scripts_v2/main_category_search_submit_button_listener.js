/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
function waitForFormAndAttachListener(retries = 20) {
    const form = document.getElementById("product-filter-form");

    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const filterData = collectMainCategoryFilters();

            // Sent alert if not categories selected
            if (!filterData || Object.keys(filterData).length === 0) {
                alert("⚠️ Please select a product category before searching.");
                return;
            }

            try {
                const translatedFilters = await fetch('/product_search_scripts_v2/translate_filters.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filters: filterData })
                }).then(res => res.json());
            console.log("Translated filters:", translatedFilters);

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

            //const apiUrl = '/product_search_scripts_v2/search_ebay.php?' + queryStringFull;
            //console.log("Proxy API URL (note: this is not what is sent--check search_ebay.php):", apiUrl);

            // Convert 'k' to 'q' in query string for eBay compatibility
            const urlParams = new URLSearchParams(queryString);
            if (urlParams.has('k')) {
                urlParams.set('q', urlParams.get('k'));
                urlParams.delete('k');
            }

            const apiUrl = '/product_search_scripts_v2/search_ebay.php?' + urlParams.toString();
            console.log("🔗 Proxy API URL:", apiUrl);

            const data = await fetch(apiUrl).then(res => res.json());

            console.log("🔗 Proxy API URL:", data.debug_url); //TESTING
            console.log("🧾 Raw eBay response:", data.raw_response); //TESTING

            if (data) {
            renderResults(data);
            //auto scroll to new results if needed
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
