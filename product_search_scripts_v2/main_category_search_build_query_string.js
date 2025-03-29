function buildQueryStringFromSearchParams(filterData) {
    const urlParams = new URLSearchParams();

    for (const [categoryName, filters] of Object.entries(filterData)) {
        const meaningfulFilters = Object.entries(filters).filter(([key, value]) => {
            // Skip known default values
            if (key.startsWith("condition") && value === "any") return false;
            if (key.startsWith("price_range") && value === "any") return false;
            if (key.startsWith("sort_order")) return false; // sort doesn't indicate intent
            if (key.startsWith("custom_price")) {
                return value.min || value.max; // only count if user typed price
            }

            return value && (
                (Array.isArray(value) && value.length > 0) ||
                (typeof value === 'string' && value.trim() !== '') ||
                (typeof value === 'object' && value !== null)
            );
        });

        if (meaningfulFilters.length > 0) {
            urlParams.set('k', categoryName);

            for (const [key, value] of filtersEntries(filters)) {
                if (Array.isArray(value)) {
                    value.forEach(v => urlParams.append(key, v));
                } else if (typeof value === 'object' && value !== null) {
                    for (const [subKey, subValue] of Object.entries(value)) {
                        if (subValue) {
                            urlParams.append(`${key}_${subKey}`, subValue);
                        }
                    }
                } else {
                    urlParams.append(key, value);
                }
            }

            break; // ✅ Stop after first category with meaningful input
        }
    }

    console.log("🔍 Final query string:", urlParams.toString());
    return urlParams.toString();
}

function filtersEntries(filters) {
    return Object.entries(filters);
}
