//<div class="toggle category-toggle selected" onclick="selectCategory(this)">[-] Bearings</div>

function selectCategory(clickedToggle) {
    // Collapse and clear all categories
    document.querySelectorAll(".category-item").forEach(categoryItem => {
        categoryItem.classList.remove("selected");

        const toggle = categoryItem.querySelector(".category-toggle");
        if (toggle) {
            toggle.textContent = toggle.textContent.replace("[-]", "[+]");
        }

        const filtersBox = categoryItem.querySelector(".category-filters");
        if (filtersBox) {
            filtersBox.style.display = "none";

            filtersBox.querySelectorAll("input").forEach(input => {
                if (input.type === "checkbox" || input.type === "radio") input.checked = false;
                if (input.type === "text") input.value = "";
            });
        }
    });

    // ✅ Apply "selected" to the full category-item div
    const categoryItem = clickedToggle.closest(".category-item");
    if (categoryItem) {
        categoryItem.classList.add("selected");

        const filtersBox = categoryItem.querySelector(".category-filters");
        if (filtersBox) {
            filtersBox.style.display = "block";
        }

        clickedToggle.textContent = clickedToggle.textContent.replace("[+]", "[-]");
    }
}

