function selectCategory(clickedToggle) {
    // Remove "selected" from all category-item blocks
    document.querySelectorAll(".category-item").forEach(categoryItem => {
        categoryItem.classList.remove("selected");

        const toggle = categoryItem.querySelector(".category-toggle");
        if (toggle) toggle.textContent = toggle.textContent.replace("[-]", "[+]");

        const filtersBox = categoryItem.querySelector(".category-filters");
        if (filtersBox) {
            filtersBox.style.display = "none";
            filtersBox.querySelectorAll("input").forEach(input => {
                if (input.type === "checkbox" || input.type === "radio") input.checked = false;
                if (input.type === "text") input.value = "";
            });
        }
    });

// ✅ Apply `.selected` to the entire category-item div
    const categoryItem = clickedToggle.closest(".category-item");
    if (categoryItem) {
        categoryItem.classList.add("selected");

        const filtersEl = categoryItem.querySelector(".category-filters");
        if (filtersEl) {
            filtersEl.style.display = "block";
        }

        clickedToggle.textContent = clickedToggle.textContent.replace("[+]", "[-]");
    }


    // Expand its filters
    const filtersEl = clickedToggle.nextElementSibling;
    filtersEl.style.display = 'block';
    clickedToggle.textContent = clickedToggle.textContent.replace('[+]', '[-]');

    // Ensure this category is "active" for the search
}

