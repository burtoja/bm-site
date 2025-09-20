/**
 * Runs search with offset to help pagination happen
 *
 * @param newOffset
 * @returns {Promise<void>}
 */
function runSearchWithOffset(offset = 0) {
    const url = new URLSearchParams(window.location.search);
    url.set('offset', offset);

    const endpoint = `/product_search_scripts_testing/backend/search_ebay.php?${url.toString()}`;

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
    const hasQ = url.has('q');
    const hasCat = url.has('cat_id') || url.has('subcat_id') || url.has('subsub_id');
    const hasFilters = Array.from(url.keys()).some(k => k.startsWith('flt['));
    if (hasQ || hasCat || hasFilters) {
        runSearchWithOffset();
    }
});
