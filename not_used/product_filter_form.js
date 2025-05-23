
console.log("🔥 product_filter_form.js loaded");
/**
 * Collects the filter selections
 * @returns {{}}
 */
function collectFilterSelections() {
    const form = document.getElementById("product-filter-form");
    const data = {};

    document.querySelectorAll('.category-item').forEach(categoryEl => {
        const categoryName = categoryEl.querySelector('.category-toggle')?.textContent.replace('[+]', '').replace('[-]', '').trim();
        if (!categoryName) return;

        const categoryData = {};

        categoryEl.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const rawName = cb.name.replace(/\[\]$/, ''); // strip [] if present
            if (!categoryData[rawName]) categoryData[rawName] = [];
            categoryData[rawName].push(cb.value);
        });

        categoryEl.querySelectorAll('input[type="radio"]:checked').forEach(rb => {
            categoryData[rb.name] = rb.value;
        });

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
 * Converts filter selections to parameters for search
 * @param filterData
 * @returns {{}}
 */
function convertToQueryParams(filterData) {
    const params = {};
    const [categoryName, filters] = Object.entries(filterData)[0] || [];

    if (!filters) return params;

    params['k'] = categoryName;

    for (const [key, value] of Object.entries(filters)) {
        if (Array.isArray(value)) {
            if (key.includes('condition')) {
                params['condition'] = value.map(v => v.toUpperCase()).join(',');
            } else {
                const label = detectFilterLabel(key);
                if (label) {
                    params[label] = value.join(',');
                } else if (key.startsWith('filter_')) {
                    // fallback: add raw filter name to query string
                    params[key] = value.join(',');
                }
            }
        } else if (typeof value === 'object' && key === 'custom_price') {
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

function detectFilterLabel(key) {
    const map = {
        'manufacturer': ['brand', 'manufacturer', 'make'],
        'type': ['type', 'model', 'style']
        // Add more mappings as needed
    };

    for (const label in map) {
        for (const possible of map[label]) {
            if (key.toLowerCase().includes(possible)) {
                return label;
            }
        }
    }

    return null;
}
