/**
 * Build structured URLSearchParams from the user's selections.
 * - category path is explicit (AND narrowing)
 * - filters keep their grouping (OR within a filter)
 * - globals preserved
 */
function buildParamsFromSelections({ categories, selected, globals }) {
    const params = new URLSearchParams();

    // category path
    if (selected.categoryPath?.categoryId)     params.set('cat_id', selected.categoryPath.categoryId);
    if (selected.categoryPath?.subcategoryId)  params.set('subcat_id', selected.categoryPath.subcategoryId);
    if (selected.categoryPath?.subsubcategoryId) params.set('subsub_id', selected.categoryPath.subsubcategoryId);

    // optional free text
    if (globals?.keywords) params.set('q', globals.keywords);

    // structured filters
    // params like flt[Face Diameter][]=2.5"&flt[Face Diameter][]=4"
    if (selected.filters) {
        Object.entries(selected.filters).forEach(([key, group]) => {
            const name = group.name || key; // allow id or name
            const vals = Array.isArray(group.values) ? group.values : [];
            vals.forEach(v => params.append(`flt[${name}][]`, v));
        });
    }

    // globals
    if (globals?.minPrice) params.set('min_price', globals.minPrice);
    if (globals?.maxPrice) params.set('max_price', globals.maxPrice);
    if (Array.isArray(globals?.condition)) {
        globals.condition.forEach(c => params.append('condition', c));
    }

    // sort token: 'price' or '-price'
    const sort = globals?.sortOrder === 'low_to_high' ? 'price' : '-price';
    params.set('sort', sort);

    return params;
}
