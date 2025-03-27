document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    if (!form) return;

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        // Step 1: collect the raw filter values
        const filterData = collectMainCategoryFilters();
        console.log("Collected filter data:", filterData);

        // Step 2: send to PHP for translation
        fetch("/product_search_scripts_v2/translate_filters.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ filters: filterData })
        })
            .then(res => res.json())
            .then(translated => {
                console.log("Translated filter data:", translated);
            })
            .catch(err => {
                console.error("Translation fetch failed:", err);
            });
    });
});
