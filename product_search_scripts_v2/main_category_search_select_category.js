function selectCategory(clickedToggle) {
    // Remove 'selected' class and collapse filters from all other categories
    document.querySelectorAll('.category-toggle').forEach(toggle => {
        toggle.parentElement.classList.remove("selected");
        toggle.textContent = toggle.textContent.replace('[-]', '[+]');
        toggle.nextElementSibling.style.display = 'none';

        // Uncheck all inputs inside other categories
        const filtersBox = toggle.nextElementSibling;
        filtersBox.querySelectorAll('input').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
            if (input.type === 'text') input.value = '';
        });
    });

    // Mark the clicked toggle as selected
    clickedToggle.parentElement.classList.add("selected");

    // Expand its filters
    const filtersEl = clickedToggle.nextElementSibling;
    filtersEl.style.display = 'block';
    clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');

    // Ensure this category is "active" for the search
}

