/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
function waitForFormAndAttachListener(retries = 20) {
    const form = document.getElementById("product-filter-form");

    if (form) {
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            console.group("🔎 SEARCH DEBUGGER");
            console.log("✅ Search button clicked.");

            const filterData = collectMainCategoryFilters();
            console.log("✅ Collected Filters:", filterData);

            // Send alert if no categories selected
            if (!filterData || Object.keys(filterData).length === 0) {
                alert("\u26a0\ufe0f Please select a product category before searching.");
                console.warn("⚠️ No filters collected. Search canceled.");
                console.groupEnd();
                return;
            }

            try {
                const translatedFilters = await fetch('/product_search_scripts_v2/translate_filters.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filters: filterData })
                }).then(res => res.json());
                console.log("✅ Received Translated Filters:", translatedFilters);

                // Build query string
                const queryString = buildQueryStringFromSearchParams(translatedFilters);
                console.log("✅ Final Query String:", queryString);

                // Parse it into URLSearchParams
                const params = new URLSearchParams(queryString);
                // Normalize param names: lowercase and replace spaces with underscores
                const normalizedParams = new URLSearchParams();
                for (const [key, value] of params.entries()) {
                    const newKey = key.toLowerCase().replace(/\s+/g, '_');
                    normalizedParams.append(newKey, value);
                }

                // If 'k' exists, replace it with 'q'
                if (normalizedParams.has('k')) {
                    normalizedParams.set('q', params.get('k'));
                    normalizedParams.delete('k');
                }

                // Make the final API URL
                const apiUrl = '/product_search_scripts_v2/search_ebay.php?' + normalizedParams.toString();
                console.log("✅ Sending API Request To:", apiUrl);

                // Fetch data
                const data = await fetch(apiUrl).then(res => res.json());
                console.log("✅ API Response:", data);

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
            console.groupEnd();
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
