/**
 *
 * @param clickedToggle
 */
function selectCategory(clickedToggle) {
    const categoryItem = clickedToggle.closest('.category-item');
    const filtersBox = categoryItem.querySelector('.category-filters');
    const subcategoryContainer = categoryItem.querySelector('.subcategory-container');
    const isAlreadySelected = clickedToggle.classList.contains('selected');
    const isExpanded = filtersBox?.style.display === 'block';

    // Collapse if already selected and open
    if (isAlreadySelected && isExpanded) {
        filtersBox.style.display = 'none';
        clickedToggle.textContent = clickedToggle.textContent.replace('[-]', '[+]');
        clickedToggle.classList.remove('selected');
        return;
    }

    // Collapse all other categories and remove selection
    document.querySelectorAll('.category-item').forEach(otherItem => {
        const otherToggle = otherItem.querySelector('.category-toggle');
        const otherFilters = otherItem.querySelector('.category-filters');

        otherItem.classList.remove('selected');
        if (otherToggle) otherToggle.classList.remove('selected');
        if (otherToggle) otherToggle.textContent = otherToggle.textContent.replace('[-]', '[+]');
        if (otherFilters) otherFilters.style.display = 'none';
    });

    // Mark this category as selected
    categoryItem.classList.add('selected');
    clickedToggle.classList.add('selected');
    if (filtersBox) filtersBox.style.display = 'block';
    clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');

    // If subcategory container is present and empty, load subcategories via AJAX
    if (subcategoryContainer && subcategoryContainer.childElementCount === 0) {
        const categoryId = subcategoryContainer.getAttribute('data-category-id');
        fetch(`/product_search_scripts/backend/get_subcategories.php?category_id=${categoryId}`)
            .then(res => res.json())
            .then(data => {
                if (Array.isArray(data.subcategories)) {
                    subcategoryContainer.innerHTML = data.subcategories.map(sub => {
                        return `
                            <div class="subcategory-item">
                                <div class="toggle subcategory-toggle" onclick="loadSubcategoryFilters(this)" data-subcategory-id="${sub.id}">[+] ${sub.name}</div>
                                <div class="subcategory-filters" style="display:none;"></div>
                            </div>
                        `;
                    }).join('');
                } else {
                    subcategoryContainer.innerHTML = "<em>No subcategories found</em>";
                }
            })
            .catch(err => {
                subcategoryContainer.innerHTML = "<em>Error loading subcategories</em>";
                console.error("Subcategory fetch error:", err);
            });
    }
}

// Make globally available for inline onclick
window.selectCategory = selectCategory;
