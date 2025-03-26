/**
 * Search button click listener
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("product-filter-form");

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const filterData = collectFilterSelections();
        const flatParams = convertToQueryParams(filterData);
        const queryString = new URLSearchParams(flatParams).toString();

        // Redirect to the existing results page
        window.location.href = '/product-listings/?' + queryString;
    });
});


/**
 *  Function to collect the slected values in the filters
 * @returns {{}}
 */
function collectFilterSelections() {
    const form = document.getElementById("product-filter-form");
    const data = {};

    // Loop through each category container
    document.querySelectorAll('.category-item').forEach(categoryEl => {
        const categoryName = categoryEl.querySelector('.category-toggle')?.textContent.replace('[+]', '').replace('[-]', '').trim();
        if (!categoryName) return;

        const categoryData = {};

        // Get all checked checkboxes in this category
        categoryEl.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const filterIdMatch = cb.name.match(/filter_(\d+)\[\]/);
            const filterKey = filterIdMatch ? `filter_${filterIdMatch[1]}` : cb.name;

            if (!categoryData[filterKey]) categoryData[filterKey] = [];
            categoryData[filterKey].push(cb.value);
        });

        // Get selected radio buttons
        categoryEl.querySelectorAll('input[type="radio"]:checked').forEach(rb => {
            categoryData[rb.name] = rb.value;
        });

        // Get custom price fields if visible
        const minInput = categoryEl.querySelector('input[name^="min_price_"]');
        const maxInput = categoryEl.querySelector('input[name^="max_price_"]');
        if (minInput?.value || maxInput?.value) {
            categoryData['custom_price'] = {
                min: minInput?.value || null,
                max: maxInput?.value || null
            };
        }

        data[categoryName] = categoryData;
    });

    return data;
}

/**
 *
 * @param key
 * @returns {null|string}
 */
function detectFilterLabel(key) {
    const map = {
        'manufacturer': ['brand', 'manufacturer', 'make'],
        'type': ['type', 'model', 'style'],
        // add more mappings as needed
    };

    for (const label in map) {
        for (const possible of map[label]) {
            if (key.toLowerCase().includes(possible)) {
                return label;
            }
        }
    }

    return null; // fallback: skip unmapped filters
}


/**
 * Convert selected values that were collected into params for the query
 * @param filterData
 * @returns {{}}
 */
function convertToQueryParams(filterData) {
    const params = {};

    // For now, use only the first selected category
    const [categoryName, filters] = Object.entries(filterData)[0] || [];

    if (!filters) return params;

    // Use the category as the keyword
    params['k'] = categoryName;

    for (const [key, value] of Object.entries(filters)) {
        if (Array.isArray(value)) {
            if (key.includes('condition')) {
                params['condition'] = value.map(v => v.toUpperCase()).join(',');
            } else {
                // Assume these are filter options (like manufacturer or type)
                const label = detectFilterLabel(key);
                if (label) {
                    params[label] = value.join(',');
                }
            }
        } else if (typeof value === 'object' && value !== null && key === 'custom_price') {
            if (value.min) params['min_price'] = value.min;
            if (value.max) params['max_price'] = value.max;
        } else if (key.startsWith('price_range') && value === 'under_100') {
            params['max_price'] = 100;
        } else if (key.startsWith('sort_order')) {
            params['sort_select'] = value === 'low_to_high' ? 'price_asc' : 'price_desc';
        }
    }

    return params;
}

