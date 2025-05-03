// load_nested_subcategories.js
// Builds a collapsible tree of nested subcategories
// Leaf nodes trigger filter loading; inner nodes toggle visibility of children

let selectedCategoryId = null;
let selectedSubcategoryPath = [];

// Load and render the root subcategories for a category
function loadTopLevelSubcategories(categoryId, toggleElement) {
    const filtersContainer = document.getElementById('category-filters-' + categoryId);
    const treeContainer = filtersContainer.querySelector('.subcategory-tree-container');

    // Toggle filters section
    const isVisible = filtersContainer.style.display === 'block';
    filtersContainer.style.display = isVisible ? 'none' : 'block';
    toggleElement.textContent = isVisible ? '[+]' + toggleElement.textContent.substring(3) : '[−]' + toggleElement.textContent.substring(3);

    if (!isVisible && treeContainer.childElementCount === 0) {
        fetch(`/product_search_scripts_v2/backend/get_subcategories.php?category_id=${categoryId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.subcategories)) {
                    data.subcategories.forEach(subcat => {
                        const node = buildSubcategoryNode(subcat);
                        treeContainer.appendChild(node);
                    });
                }
            });
    }
}

// Build a single subcategory tree node (LI with toggle and children container)
function buildSubcategoryNode(subcat) {
    const wrapper = document.createElement('div');
    wrapper.className = 'subcategory-node';

    const header = document.createElement('div');
    header.className = 'subcategory-header';
    header.innerHTML = `<span class="toggle-icon">[+]</span> ${subcat.name}`;
    header.onclick = () => toggleSubcategoryChildren(subcat, wrapper);

    wrapper.appendChild(header);
    return wrapper;
}

// Expand/collapse logic for tree nodes and loading children if needed
function toggleSubcategoryChildren(subcat, wrapper) {
    let childContainer = wrapper.querySelector('.subcategory-children');
    const toggleIcon = wrapper.querySelector('.toggle-icon');

    if (childContainer) {
        const isVisible = childContainer.style.display === 'block';
        childContainer.style.display = isVisible ? 'none' : 'block';
        toggleIcon.textContent = isVisible ? '[+]' : '▼';
        return;
    }

    fetch(`/product_search_scripts_v2/backend/get_child_subcategories.php?parent_id=${subcat.id}`)
        .then(res => res.text())
        .then(text => {
            console.log("Raw response:", text); // DEBUG line
            return JSON.parse(text);
        })
        .then(data => {
            if (data.success && data.subcategories.length > 0) {
                childContainer = document.createElement('div');
                childContainer.className = 'subcategory-children';
                data.subcategories.forEach(child => {
                    const childNode = buildSubcategoryNode(child);
                    childContainer.appendChild(childNode);
                });
                wrapper.appendChild(childContainer);
                toggleIcon.textContent = '▼';
            } else {
                loadFiltersForSubcategory(subcat.id);
            }
        })
        .catch(err => {
            console.error("Failed to load subcategories or filters:", err);
        });
}

// Load filters associated with a selected leaf subcategory
/**
 * Loads and renders filter blocks for a given subcategory.
 * @param {number} subcategoryId - The leaf subcategory ID to load filters for.
 * @param {HTMLElement} targetElement - The DOM element where filters should be inserted.
 */
async function loadFiltersForSubcategory(subcategoryId, targetElement) {
    console.log(`Loading filters for subcategory ID: ${subcategoryId}`);
    try {
        const res = await fetch(`/product_search_scripts_v2/backend/get_subcategory_filters.php?subcategory_id=${subcategoryId}`);
        const text = await res.text();
        const data = JSON.parse(text);

        // Clear previous filters (if reloading)
        targetElement.innerHTML = '';

        if (!data.filters || data.filters.length === 0) {
            targetElement.innerHTML = '<p class="no-filters">No filters available for this subcategory.</p>';
            return;
        }

        // Build each filter group
        data.filters.forEach(filter => {
            const group = document.createElement('div');
            group.className = 'filter-group';

            const label = document.createElement('label');
            label.textContent = filter.filter_name;
            group.appendChild(label);

            const select = document.createElement('select');
            select.name = `filter_${filter.filter_id}`;
            select.multiple = true;

            filter.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.option_id;
                option.textContent = opt.value;
                select.appendChild(option);
            });

            group.appendChild(select);
            targetElement.appendChild(group);
        });

    } catch (err) {
        console.error('Error loading filters:', err);
        targetElement.innerHTML = '<p class="error">Failed to load filters.</p>';
    }
}

// Render filter groups and their checkbox options
function renderFiltersForContainer(filters, container) {
    container.innerHTML = '';

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
            checkbox.id = `opt_${filter.filter_id}_${opt.option_id}`;

            const text = document.createElement('label');
            text.setAttribute('for', checkbox.id);
            text.textContent = opt.value;
            text.prepend(checkbox);

            group.appendChild(text);
        });

        container.appendChild(group);
    });
}

