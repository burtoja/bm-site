//<div class="toggle category-toggle selected" onclick="selectCategory(this)">[-] Bearings</div>

function selectCategory(clickedToggle) {
    // Remove .selected from all category-item blocks
    document.querySelectorAll('.category-item').forEach(categoryItem => {
        categoryItem.classList.remove('selected');

        const toggle = categoryItem.querySelector('.category-toggle');
        if (toggle) toggle.classList.remove('selected');
        if (toggle) toggle.textContent = toggle.textContent.replace('[-]', '[+]');

        const filtersBox = categoryItem.querySelector('.category-filters');
        if (filtersBox) {
            filtersBox.style.display = 'none';
            filtersBox.querySelectorAll('input').forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                if (input.type === 'text') input.value = '';
            });
        }
    });

    // ✅ Add .selected to the clicked category-toggle
    clickedToggle.classList.add('selected');

    const categoryItem = clickedToggle.closest('.category-item');
    if (categoryItem) {
        categoryItem.classList.add('selected');

        const filtersBox = categoryItem.querySelector('.category-filters');
        if (filtersBox) {
            filtersBox.style.display = 'block';
        }

        clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');
    }
}

// ✅ Immediately expose the function globally
window.selectCategory = selectCategory;

// ✅ Optional: log when the file loads
console.log("✅ selectCategory.js loaded");




