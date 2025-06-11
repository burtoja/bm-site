/**
 * Runs search with offset to help pagination happen
 *
 * @param newOffset
 * @returns {Promise<void>}
 */
function runSearchWithOffset(offset = 0) {
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    const sort = params.get('sort');
    const minPrice = params.get('min_price');
    const maxPrice = params.get('max_price');
    const conditions = params.getAll('condition');

    if (!q) {
        console.warn("Missing 'q' parameter in URL. Cannot run search.");
        return;
    }

    const searchParams = new URLSearchParams();
    searchParams.set('q', q);
    if (sort) searchParams.set('sort', sort);
    if (minPrice) searchParams.set('min_price', minPrice);
    if (maxPrice) searchParams.set('max_price', maxPrice);
    conditions.forEach(c => searchParams.append('condition', c));
    searchParams.set('offset', offset);

    const endpoint = `/product_search_scripts_testing/backend/search_ebay.php?${searchParams.toString()}`;

    // Optional: show loading spinner
    //document.getElementById("search-results").innerHTML = "<p>Loading results...</p>";

    document.getElementById("search-results").innerHTML = '' +
        '<div class="result-card flex flex-col items-center justify-center text-center"><div class="result-image"></div><div class="animate-pulse">Loading...</div></div> ' +
        '<div class="result-card flex flex-col items-center justify-center text-center"><div class="result-image"></div><div class="animate-pulse">Loading...</div></div> ' +
        '<div class="result-card flex flex-col items-center justify-center text-center"><div class="result-image"></div><div class="animate-pulse">Loading...</div></div> ' +
        '<div class="result-card flex flex-col items-center justify-center text-center"><div class="result-image"></div><div class="animate-pulse">Loading...</div></div> ' +
        '</div>';

    fetch(endpoint)
        .then(res => res.json())
        .then(data => {
            renderResults(data);
            document.getElementById('search-results').scrollIntoView({behavior: 'smooth'});
            renderPagination(data.total, data.offset, 50);
        })
        .catch(error => {
            console.error("Error during paginated search:", error);
            document.getElementById("search-results").innerHTML = "<p>Error loading results.</p>";
        });
}

// ensures the search auto-runs if the user refreshes the page or shares a link with query params
window.addEventListener('DOMContentLoaded', () => {
    const url = new URLSearchParams(window.location.search);
    if (url.has('q')) {
        runSearchWithOffset();
    }
});
