/**
 * Listens for and executes actions associated with the search products (submit) button.
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    if (!form) return;

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const filterData = collectMainCategoryFilters();
        console.log("Collected filter data:", filterData);

        fetch("/product_search_scripts_v2/translate_filters.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ filters: filterData })
        })
            .then(res => {
                console.log("Received response:", res);

                if (!res.ok) {
                    console.error("🚫 Response not OK", res.status, res.statusText);
                    return;
                }

                return res.json();
            })
            .then(translatedData => {
                if (!translatedData) {
                    console.warn("⚠️ No translated data returned");
                    return;
                }

                console.log("Translated filter data:", translatedData);

                console.log("About to call extractSearchParameters()");
                const searchParams = extractSearchParameters(translatedData);
                console.log("Search parameters:", searchParams);

                const queryString = buildQueryStringFromSearchParams(searchParams);
                console.log("🔗 Final Query String:", queryString);
            })
            .catch(err => {
                console.error("Translation fetch failed:", err);
            });




    });

});
