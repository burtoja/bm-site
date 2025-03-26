/**
 * Listener for the search button
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    form?.addEventListener("submit", function (e) {
        e.preventDefault();

        const filterData = collectFilterSelections();
        const flatParams = convertToQueryParams(filterData);
        const queryString = new URLSearchParams(flatParams).toString();

        console.log("TESTING LISTENER");
        console.log("Collected filter data:", filterData);
        console.log("Redirecting to the following:", queryString);

        window.location.href = '/product-listings/?' + queryString;
    });
});