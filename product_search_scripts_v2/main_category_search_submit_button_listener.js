/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
function waitForFormAndAttachListener(retries = 20) {
    const form = document.getElementById("product-filter-form");

    if (form) {
        console.log("Found form: attaching submit listener");

        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const filterData = collectMainCategoryFilters();
            console.log("Collected filter data:", filterData);

            const queryString = buildQueryStringFromSearchParams(filterData);
            console.log("Built query string:", queryString);

            const params = new URLSearchParams(queryString);
            console.log("Params object:", params.toString());

            const apiUrl = buildEbayApiEndpointFromParams(params);
            console.log("API endpoint:", apiUrl);

            const data = await fetchEbayData(apiUrl);
            if (data) {
                renderResults(data);
            } else {
                document.getElementById('search-results').innerHTML = '<p>No results found.</p>';
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





// OLD LISTENER WITHOUT THE WAIT
// document.addEventListener("DOMContentLoaded", function () {
//     const form = document.getElementById("product-filter-form");
//
//     if (!form) {
//         console.error("Could not find identifiable element: #product-filter-form");
//         return;
//     }
//
//     form.addEventListener("submit", async function (e) {
//         e.preventDefault();
//
//         const filterData = collectMainCategoryFilters();
//         const flatParams = buildQueryStringFromSearchParams(filterData);
//         const queryString = new URLSearchParams(flatParams);
//         const apiUrl = buildEbayApiEndpointFromParams(queryString);
//
//         fetchEbayData(apiUrl).then(data => {
//             if (data) {
//                 renderResults(data);
//             } else {
//                 document.getElementById('search-results').innerHTML = '<p>No results found.</p>';
//             }
//         });
//     });
//
//
// });
