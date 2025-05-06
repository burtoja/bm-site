/**
 * Loads filters for a subcategory and injects them into the DOM
 * @param {HTMLElement} clickedToggle
 */
function loadSubcategoryFilters(clickedToggle) {
    const subcategoryItem = clickedToggle.closest('.subcategory-item');
    const filtersBox = subcategoryItem.querySelector('.subcategory-filters');
    const isExpanded = filtersBox?.style.display === 'block';
    const subcategoryId = clickedToggle.getAttribute('data-subcategory-id');

    // Collapse if already expanded
    if (isExpanded) {
        filtersBox.style.display = 'none';
        clickedToggle.textContent = clickedToggle.textContent.replace('[-]', '[+]');
        return;
    }

    // Collapse other subcategories
    document.querySelectorAll('.subcategory-item').forEach(other => {
        const otherToggle = other.querySelector('.subcategory-toggle');
        const otherFilters = other.querySelector('.subcategory-filters');
        if (otherToggle) otherToggle.textContent = otherToggle.textContent.replace('[-]', '[+]');
        if (otherFilters) otherFilters.style.display = 'none';
    });

    // Expand this one
    filtersBox.style.display = 'block';
    clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');

    // Load filters via AJAX if not already loaded
    if (filtersBox.childElementCount === 0) {
        fetch(`/product_search_scripts_v2/backend/get_subcategory_filters.php?subcategory_id=${subcategoryId}`)
            .then(res => res.json())
            .then(data => {
                console.log("Loaded filters for subcategory:", data.filters);
                if (Array.isArray(data.filters)) {
                    filtersBox.innerHTML = data.filters.map(f => {
                        const optionsHtml = f.options.map(o => `
                            <li><label><input type="checkbox" name="filter_${f.filter_id}[]" value="${o.option_id}"> ${o.value}</label></li>
                        `).join('');
                        return `
                            <div class="filter-item">
                                <div class="toggle filter-toggle" onclick="toggleVisibility(this)">[+] ${f.filter_name}</div>
                                <div class="filter-options" style="display:none;">
                                    <ul>${optionsHtml}</ul>
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    filtersBox.innerHTML = "<em>No filters found</em>";
                }
            })
            .catch(err => {
                filtersBox.innerHTML = "<em>Error loading filters</em>";
                console.error("Filter fetch error:", err);
            });
    }
}

window.loadSubcategoryFilters = loadSubcategoryFilters;
