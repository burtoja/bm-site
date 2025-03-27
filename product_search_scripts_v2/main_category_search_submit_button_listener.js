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
            .then(res => res.json())
            .then(translatedData => {
                console.log("Translated filter data:", translatedData);

                // ✅ USE IT HERE — translatedData is now defined
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
