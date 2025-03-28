/**
 * Generates an endpoint url from a param of the form:
 *
 * /product-listings/?k=Pumps&condition=NEW&type=Submersible&manufacturer=Dayton&min_price=100&max_price=500&sort_select=price_desc
 *
 * It returns an endpoint of the form:
 *
 * https://api.ebay.com/buy/browse/v1/item_summary/search?q=Pumps
 * &filter=conditions:{NEW},price:[100..500]
 * &aspect_filter=categoryId:xxx,type:{Submersible},manufacturer:{Dayton}
 * &sort=-price
 *
 * @param params
 * @returns {string}
 */
function buildEbayApiEndpointFromParams(params) {
    const baseUrl = 'https://api.ebay.com/buy/browse/v1/item_summary/search';
    const filters = [];
    const aspects = [];

    // Core keyword
    const keyword = params.get('k') || '';
    const queryParts = [`q=${encodeURIComponent(keyword)}`];

    // Condition (eBay expects NEW, USED, etc.)
    if (params.has('condition')) {
        const condition = params.get('condition');
        filters.push(`conditions:{${condition}}`);
    }

    // Price Range
    const min = params.get('min_price');
    const max = params.get('max_price');
    if (min || max) {
        const minVal = min || '0';
        const maxVal = max || '*';
        filters.push(`price:[${minVal}..${maxVal}]`);
    }

    // Manufacturer and Type as aspects
    if (params.has('manufacturer')) {
        const manufacturer = params.get('manufacturer');
        aspects.push(`manufacturer:{${manufacturer}}`);
    }

    if (params.has('type')) {
        const type = params.get('type');
        aspects.push(`type:{${type}}`);
    }

    // Sort
    if (params.get('sort_select') === 'price_asc') {
        queryParts.push('sort=price');
    } else if (params.get('sort_select') === 'price_desc') {
        queryParts.push('sort=-price');
    }

    // Combine filters
    if (filters.length > 0) {
        queryParts.push('filter=' + filters.join(','));
    }

    if (aspects.length > 0) {
        queryParts.push('aspect_filter=' + aspects.join(','));
    }

    console.log("Api endpoint:  " + `${baseUrl}?${queryParts.join('&')}`);  //TESTING

    return `${baseUrl}?${queryParts.join('&')}`;
}
