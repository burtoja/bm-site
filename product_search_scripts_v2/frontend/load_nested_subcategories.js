// load_nested_subcategories.js
// Builds a collapsible tree of nested subcategories
// Leaf nodes trigger filter loading; inner nodes toggle visibility of children

let selectedCategoryId = null;
let selectedSubcategoryPath = [];

// Load and render the root subcategories for a category
function loadTopLevelSubcategories(categoryId) {
    selectedCategoryId = categoryId;
    selectedSubcategoryPath = [];
    document.getElementById('subcategory-container').innerHTML = '';

    fetch(`/product_search_scripts_v2/backend/get_subcategories.php?category_id=${categoryId}`)
        .then(res => res.json())
        .then(subcategories => {
            const treeRoot = document.createElement('ul');
            treeRoot.className = 'subcategory-tree';
            subcategories.forEach(subcat => {
                const node = buildSubcategoryNode(subcat);
                treeRoot.appendChild(node);
            });
            document.getElementById('subcategory-container').appendChild(treeRoot);
        })
        .catch(err => console.error('Error loading subcategories:', err));
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
