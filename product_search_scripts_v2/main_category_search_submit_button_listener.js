/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    if (!form) {
        console.error("Could not find identifiable element: #product-filter-form");
        return;
    }

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const filterData = collectMainCategoryFilters();
        const flatParams = buildQueryStringFromSearchParams(filterData);
        const queryString = new URLSearchParams(flatParams);
        const apiUrl = buildEbayApiEndpointFromParams(queryString);

        fetchEbayData(apiUrl).then(data => {
            if (data) {
                renderResults(data);
            } else {
                document.getElementById('search-results').innerHTML = '<p>No results found.</p>';
            }
        });
    });


});
