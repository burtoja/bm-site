/**
 * Build structured URLSearchParams from the user's selections.
 * - category path is explicit (AND narrowing)
 * - filters keep their grouping (OR within a filter)
 * - globals preserved
 */
function buildParamsFromSelections({ selected, globals }) {
    const params = new URLSearchParams();

    // category path
    const cp = selected?.categoryPath || {};

    // Internal IDs (useful for your own app state; not used as eBay category)
    if (cp.categoryId)       params.set('cat_id', cp.categoryId);
    if (cp.subcategoryId)    params.set('subcat_id', cp.subcategoryId);
    if (cp.subsubcategoryId) params.set('subsub_id', cp.subsubcategoryId);

    // Names (used to enrich q on the backend)
    if (cp.categoryName)       params.set('cat_name', cp.categoryName);
    if (cp.subcategoryName)    params.set('subcat_name', cp.subcategoryName);
    if (cp.subsubcategoryName) params.set('subsub_name', cp.subsubcategoryName);

    // optional free text
    if (globals?.keywords) params.set('q', globals.keywords);

    // structured filters
    // params like flt[Face Diameter][]=2.5"&flt[Face Diameter][]=4"
    const groups = selected?.filters || {};
    Object.keys(groups).forEach(name => {
        const vals = groups[name]?.values || [];
        vals.forEach(v => params.append(`flt[${name}][]`, v));
    });

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


