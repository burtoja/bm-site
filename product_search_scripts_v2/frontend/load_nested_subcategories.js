// load_nested_subcategories.js
// Builds and manages a collapsible, recursive tree of subcategories
// Leaf nodes load filters; parent nodes toggle child visibility

console.log("BEGIN: load_nested_subcategories");
let selectedCategoryId = null;
let selectedSubcategoryPath = [];

/**
 * Loads and renders the top-level subcategories for a given root category.
 * Called when a top-level category is clicked/toggled.
 * @param {number} categoryId - ID of the root category.
 * @param {HTMLElement} toggleElement - The [+]/[−] toggle element next to the category title.
 */
function loadTopLevelSubcategories(categoryId, toggleElement) {
    console.log("LoadTopLevelSubcategories() called.");
    const filtersContainer = document.getElementById('category-filters-' + categoryId);
    const treeContainer = filtersContainer.querySelector('.subcategory-tree-container');

    // Toggle visibility
    const isVisible = filtersContainer.style.display === 'block';
    filtersContainer.style.display = isVisible ? 'none' : 'block';

    // Update the toggle icon text
    toggleElement.textContent = isVisible
        ? '[+]' + toggleElement.textContent.substring(3)
        : '[−]' + toggleElement.textContent.substring(3);

    // If children are already loaded, don't fetch again
    if (!isVisible && treeContainer.childElementCount === 0) {
        fetch(`/product_search_scripts_v2/backend/get_subcategories.php?category_id=${categoryId}`)
            .then(res => res.json())
            .then(data => {
                console.log("Fetched data:", data);
                console.log("treeContainer:", treeContainer);
                if (Array.isArray(data.subcategories)) {
                    data.subcategories.forEach(subcat => {
                        console.log("Building node for:", subcat);
                        const node = buildSubcategoryNode(subcat);
                        treeContainer.appendChild(node);
                    });
                } else {
                    console.warn("No subcategories array found in response.");
                }
            })
            .catch(err => {
                console.error("Failed to load top-level subcategories:", err);
            });
    }
}

/**
 * Builds a subcategory tree node (DOM element) with click-to-expand behavior.
 * @param {Object} subcat - Subcategory object { id, name }.
 * @returns {HTMLElement} DOM node representing the subcategory.
 */
function buildSubcategoryNode(subcat) {
    const wrapper = document.createElement('div');
    wrapper.className = 'subcategory-node';

    const header = document.createElement('div');
    header.className = 'subcategory-header';
    header.innerHTML = `<span class="toggle-icon">[+]</span> ${subcat.name}`;

    // Handle click to expand or load filters (if it's a leaf)
    header.onclick = () => toggleSubcategoryChildren(subcat, wrapper);

    wrapper.appendChild(header);
    return wrapper;
}

/**
 * Expands a subcategory node to load its children or marks it as a leaf.
 * If already expanded, toggles visibility instead of re-fetching.
 * @param {Object} subcat - Subcategory data.
 * @param {HTMLElement} wrapper - The DOM wrapper for this node.
 */
function toggleSubcategoryChildren(subcat, wrapper) {
    let childContainer = wrapper.querySelector('.subcategory-children');
    const toggleIcon = wrapper.querySelector('.toggle-icon');

    // If children are already loaded, just toggle visibility
    if (wrapper.getAttribute('data-loaded') === 'true') {
        const isVisible = childContainer.style.display === 'block';
        childContainer.style.display = isVisible ? 'none' : 'block';
        toggleIcon.textContent = isVisible ? '[+]' : '▼';
        return;
    }

    if (subcat.has_children) {
        // Fetch and render child subcategories
        fetch(`/product_search_scripts_v2/backend/get_child_subcategories.php?parent_id=${subcat.id}&category_id=${subcat.category_id}`)
            .then(res => res.json())
            .then(data => {
                if (Array.isArray(data.subcategories) && data.subcategories.length > 0) {
                    childContainer = document.createElement('div');
                    childContainer.className = 'subcategory-children';

                    data.subcategories.forEach(child => {
                        const childNode = buildSubcategoryNode(child);
                        childContainer.appendChild(childNode);
                    });

                    wrapper.appendChild(childContainer);
                    childContainer.style.display = 'block';
                    wrapper.setAttribute('data-loaded', 'true');
                    toggleIcon.textContent = '▼';
                } else {
                    console.warn("Expected children, but none were returned for subcat ID", subcat.id);
                }
            })
            .catch(err => {
                console.error("Failed to load child subcategories:", err);
            });

    } else {
        // It’s a leaf node — load filters
        wrapper.classList.add('leaf-node');
        document.querySelectorAll('.leaf-node.selected').forEach(el => el.classList.remove('selected'));
        wrapper.classList.add('selected');

        // OLD
        // const filtersContainer = document.getElementById('filters-output');
        // console.log("Prepare to try loading filters for subcat ID:", subcat.id);
        // loadFiltersForSubcategory(subcat.id, filtersContainer);

        // Traverse upward to find the enclosing .category-filters div
        const categoryFiltersWrapper = wrapper.closest('.category-filters');
        if (categoryFiltersWrapper) {
            loadFiltersForSubcategory(subcat.id, categoryFiltersWrapper);
        } else {
            console.warn("Could not find category-filters container for subcat ID:", subcat.id);
        }
    }
}


/**
 * Loads and displays filters for a given subcategory (leaf node).
 * @param {number} subcategoryId - The selected leaf subcategory ID.
 * @param {HTMLElement} targetElement - The DOM container to insert filter elements.
 */
async function loadFiltersForSubcategory(subcategoryId, targetElement) {
    console.log(`Loading filters for subcategory ID: ${subcategoryId}`);

    // Clear previous filters
    targetElement.innerHTML = '';
    console.log("Prepare to try loading filters");
    try {
        const res = await fetch(`/product_search_scripts_v2/backend/get_subcategory_filters.php?subcategory_id=${subcategoryId}`);
        const text = await res.text();
        const data = JSON.parse(text);

        //TESTING

        console.log("The response from get_subcategory_filters--> " + text);
        console.log("Filter data:", data);
        if (!data.filters) {
            console.log("data filters is untrue");
        }
        if (data.filters.length === 0) {
            console.log("data filters length is 0");
        }
        //END


        if (!data.filters || data.filters.length === 0) {
            targetElement.innerHTML = '<p class="no-filters">No filters available for this subcategory.</p>';
            return;
        }

        // Build and display each filter group
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

// Make sure these functions will show console logs
window.loadTopLevelSubcategories = loadTopLevelSubcategories;
window.toggleSubcategoryChildren = toggleSubcategoryChildren;
window.loadFiltersForSubcategory = loadFiltersForSubcategory;

