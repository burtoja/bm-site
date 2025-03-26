document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const filterData = collectFilterSelections();
        const apiUrl = buildApiEndpoint(filterData);

        console.log("Calling API:", apiUrl);
        fetchSearchResults(apiUrl);
    });
});

function collectFilterSelections() {
    const form = document.getElementById("product-filter-form");
    const data = {};

    // Loop through each category container
    document.querySelectorAll('.category-item').forEach(categoryEl => {
        const categoryName = categoryEl.querySelector('.category-toggle')?.textContent.replace('[+]', '').replace('[-]', '').trim();
        if (!categoryName) return;

        const categoryData = {};

        // Get all checked checkboxes in this category
        categoryEl.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const filterIdMatch = cb.name.match(/filter_(\d+)\[\]/);
            const filterKey = filterIdMatch ? `filter_${filterIdMatch[1]}` : cb.name;

            if (!categoryData[filterKey]) categoryData[filterKey] = [];
            categoryData[filterKey].push(cb.value);
        });

        // Get selected radio buttons
        categoryEl.querySelectorAll('input[type="radio"]:checked').forEach(rb => {
            categoryData[rb.name] = rb.value;
        });

        // Get custom price fields if visible
        const minInput = categoryEl.querySelector('input[name^="min_price_"]');
        const maxInput = categoryEl.querySelector('input[name^="max_price_"]');
        if (minInput?.value || maxInput?.value) {
            categoryData['custom_price'] = {
                min: minInput?.value || null,
                max: maxInput?.value || null
            };
        }

        data[categoryName] = categoryData;
    });

    return data;
}
