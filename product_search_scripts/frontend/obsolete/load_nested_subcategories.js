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
        fetch(`/product_search_scripts/backend/get_subcategories.php?category_id=${categoryId}`)
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
        toggleIcon.textContent = isVisible ? '[+]' : '[-]';
        return;
    }

    if (subcat.has_children) {
        // Fetch and render child subcategories
        fetch(`/product_search_scripts/backend/get_child_subcategories.php?parent_id=${subcat.id}&category_id=${subcat.category_id}`)
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
                    toggleIcon.textContent = '[-]';
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

        const filtersContainer = wrapper.closest('.category-filters')?.querySelector('.subcategory-filters-output');
        if (filtersContainer) {
            loadFiltersForSubcategory(subcat.id, filtersContainer);
        } else {
            console.warn("Could not find .subcategory-filters-output for subcat ID:", subcat.id);
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
        const res = await fetch(`/product_search_scripts/backend/get_subcategory_filters.php?subcategory_id=${subcategoryId}`);
        const text = await res.text();
        const data = JSON.parse(text);

        console.log("The response from get_subcategory_filters--> " + text);
        console.log("Filter data:", data);

        if (!data.filters || data.filters.length === 0) {
            targetElement.innerHTML = '<p class="no-filters">No filters available for this subcategory.</p>';
            return;
        }

        // Loop through each filter and build collapsible checkbox groups
        data.filters.forEach(filter => {
            const filterItem = document.createElement('div');
            filterItem.className = 'filter-item';

            // Toggle title
            const toggle = document.createElement('div');
            toggle.className = 'toggle filter-toggle';
            toggle.textContent = `[+] ${filter.filter_name}`;
            toggle.onclick = () => {
                const optionsDiv = toggle.nextElementSibling;
                const isVisible = optionsDiv.style.display === 'block';
                optionsDiv.style.display = isVisible ? 'none' : 'block';
                toggle.textContent = isVisible ? `[+] ${filter.filter_name}` : `[−] ${filter.filter_name}`;
            };
            filterItem.appendChild(toggle);

            // Filter options container
            const optionsDiv = document.createElement('div');
            optionsDiv.className = 'filter-options';
            optionsDiv.style.display = 'none';

            const ul = document.createElement('ul');
            filter.options.forEach(opt => {
                const li = document.createElement('li');
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.name = `filter_${filter.filter_id}[]`;
                input.value = opt.option_id;
                label.appendChild(input);
                label.append(` ${opt.value}`);
                li.appendChild(label);
                ul.appendChild(li);
            });

            optionsDiv.appendChild(ul);
            filterItem.appendChild(optionsDiv);
            targetElement.appendChild(filterItem);
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

