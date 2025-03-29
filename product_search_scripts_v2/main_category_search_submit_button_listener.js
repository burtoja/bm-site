/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    if (!form) return;

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const filterData = collectMainCategoryFilters();
        const flatParams = convertToQueryParams(filterData);
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
