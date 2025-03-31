function selectCategory(clickedToggle) {
    const categoryItem = clickedToggle.closest('.category-item');
    const filtersBox = categoryItem.querySelector('.category-filters');
    const isAlreadySelected = clickedToggle.classList.contains('selected');
    const isExpanded = filtersBox?.style.display === 'block';

    // Collapse all other categories
    document.querySelectorAll('.category-item').forEach(otherItem => {
        const toggle = otherItem.querySelector('.category-toggle');
        const filters = otherItem.querySelector('.category-filters');

        if (otherItem !== categoryItem) {
            toggle?.classList.remove('selected');
            otherItem.classList.remove('selected');
            if (filters) filters.style.display = 'none';
            if (toggle) toggle.textContent = toggle.textContent.replace('[-]', '[+]');
        }
    });

    // If same category is open, toggle to collapse
    if (isAlreadySelected && isExpanded) {
        filtersBox.style.display = 'none';
        clickedToggle.textContent = clickedToggle.textContent.replace('[-]', '[+]');
        return;
    }

    // Expand and re-select the clicked category
    clickedToggle.classList.add('selected');
    categoryItem.classList.add('selected');

    if (filtersBox) {
        filtersBox.style.display = 'block';
    }

    clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');
}

// Expose globally
window.selectCategory = selectCategory;
console.log("Msg: selectCategory.js loaded");
