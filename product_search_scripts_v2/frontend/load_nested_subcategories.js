// load_nested_subcategories.js
// Builds a collapsible tree of nested subcategories
// Leaf nodes trigger filter loading; inner nodes toggle visibility of children

let selectedCategoryId = null;
let selectedSubcategoryPath = [];

// Load and render the root subcategories for a category
function loadTopLevelSubcategories(categoryId, toggleElement) {
    const container = document.getElementById(`category-filters-${categoryId}`);
    if (!container) {
        console.error(`Container not found for category ID ${categoryId}`);
        return;
    }

    // Toggle visibility
    if (container.style.display === "none") {
        container.style.display = "block";
        toggleElement.textContent = toggleElement.textContent.replace("[+]", "[-]");

        const subcatContainer = container.querySelector('.subcategory-tree-container');
        if (subcatContainer && subcatContainer.children.length === 0) {
            // Only load if not already loaded
            fetch(`/product_search_scripts_v2/backend/get_child_subcategories.php?category_id=${categoryId}&parent_id=0`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.subcategories.length > 0) {
                        renderSubcategoryTree(data.subcategories, subcatContainer);
                    } else {
                        subcatContainer.innerHTML = "<p>No subcategories found.</p>";
                    }
                });
        }
    } else {
        container.style.display = "none";
        toggleElement.textContent = toggleElement.textContent.replace("[-]", "[+]");
    }
}


// Build a single subcategory tree node (LI with toggle and children container)
function buildSubcategoryNode(subcat) {
    const li = document.createElement('li');
    li.className = 'subcategory-node';
    li.dataset.id = subcat.id;

    const toggle = document.createElement('span');
    toggle.className = 'toggle';
    toggle.textContent = '▶';
    toggle.onclick = () => toggleSubcategoryChildren(li, subcat);

    const label = document.createElement('span');
    label.className = 'subcategory-label';
    label.textContent = subcat.name;

    li.appendChild(toggle);
    li.appendChild(label);

    const childContainer = document.createElement('ul');
    childContainer.className = 'subcategory-children';
    childContainer.style.display = 'none';
    li.appendChild(childContainer);

    return li;
}

// Expand/collapse logic for tree nodes and loading children if needed
function toggleSubcategoryChildren(parentLi, subcat) {
    const childContainer = parentLi.querySelector('.subcategory-children');
    const toggleIcon = parentLi.querySelector('.toggle');

    if (childContainer.children.length > 0) {
        // Already loaded — just toggle
        const isVisible = childContainer.style.display === 'block';
        childContainer.style.display = isVisible ? 'none' : 'block';
        toggleIcon.textContent = isVisible ? '▶' : '▼';
        return;
    }

    // Load children from backend
    fetch(`/product_search_scripts_v2/backend/get_child_subcategories.php?parent_id=${subcat.id}`)
        .then(res => res.json())
        .then(children => {
            if (children.length > 0) {
                children.forEach(child => {
                    const childNode = buildSubcategoryNode(child);
                    childContainer.appendChild(childNode);
                });
                childContainer.style.display = 'block';
                toggleIcon.textContent = '▼';
            } else {
                // No children = leaf → load filters
                loadFiltersForSubcategory(subcat.id);
            }
        })
        .catch(err => console.error('Error loading child subcategories:', err));
}

// Load filters associated with a selected leaf subcategory
function loadFiltersForSubcategory(subcategoryId) {
    console.log('Loading filters for subcategory ID:', subcategoryId);
    fetch(`/product_search_scripts_v2/backend/get_subcategory_filters.php?subcategory_id=${subcategoryId}`)
        .then(res => res.json())
        .then(data => {
            renderFilters(data.filters);
        })
        .catch(err => console.error('Error loading filters:', err));
}

// Render filter groups and their checkbox options
function renderFilters(filters) {
    const filterContainer = document.getElementById('filter-container');
    filterContainer.innerHTML = '';

    filters.forEach(filter => {
        const group = document.createElement('div');
        group.className = 'filter-group';

        const label = document.createElement('h4');
        label.textContent = filter.filter_name;
        group.appendChild(label);

        filter.options.forEach(opt => {
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = `filter_${filter.filter_id}`;
            checkbox.value = opt.option_id;

            const text = document.createElement('label');
            text.textContent = opt.value;
            text.prepend(checkbox);

            group.appendChild(text);
        });

        filterContainer.appendChild(group);
    });
}
