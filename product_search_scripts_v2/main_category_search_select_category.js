function selectCategory(clickedToggle) {
    const categoryItem = clickedToggle.closest('.category-item');
    const filtersBox = categoryItem.querySelector('.category-filters');
    const isAlreadySelected = clickedToggle.classList.contains('selected');
    const isExpanded = filtersBox?.style.display === 'block';

    // If the same category is clicked and it's expanded → collapse it
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
}

// Make globally available for inline onclick
window.selectCategory = selectCategory;
console.log("Msg: selectCategory.js loaded");
