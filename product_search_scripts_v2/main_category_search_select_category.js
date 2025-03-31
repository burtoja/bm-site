function selectCategory(clickedToggle) {
    const categoryItem = clickedToggle.closest('.category-item');
    const filtersBox = categoryItem.querySelector('.category-filters');
    const isAlreadySelected = clickedToggle.classList.contains('selected');
    const isExpanded = filtersBox?.style.display === 'block';

    // First, collapse and unselect all other categories
    document.querySelectorAll('.category-item').forEach(otherItem => {
        const toggle = otherItem.querySelector('.category-toggle');
        const filters = otherItem.querySelector('.category-filters');

        toggle?.classList.remove('selected');
        otherItem.classList.remove('selected');
        if (filters) {
            filters.style.display = 'none';
        }

        if (toggle) {
            toggle.textContent = toggle.textContent.replace('[-]', '[+]');
        }
    });

    // If it was already selected and expanded → collapse only
    if (isAlreadySelected && isExpanded) {
        filtersBox.style.display = 'none';
        clickedToggle.textContent = clickedToggle.textContent.replace('[-]', '[+]');
        // ✅ Keep it selected
        clickedToggle.classList.add('selected');
        categoryItem.classList.add('selected');
        return;
    }

    // Otherwise → expand and highlight this category
    clickedToggle.classList.add('selected');
    categoryItem.classList.add('selected');

    if (filtersBox) {
        filtersBox.style.display = 'block';
    }

    clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');
}
