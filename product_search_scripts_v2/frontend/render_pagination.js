/**
 * Builds and renders the pagination bar under the search results
 *
 * @param {number} totalResults - Total number of items from eBay
 * @param {number} currentOffset - Current offset (0, 50, 100, etc.)
 * @param {number} limit - Number of items per page (default: 50)
 */
function renderPagination(totalResults, currentOffset, limit = 50) {
    const totalPages = Math.ceil(totalResults / limit);
    const currentPage = Math.floor(currentOffset / limit) + 1;

    const paginationEl = document.getElementById('pagination');
    paginationEl.innerHTML = ''; // Clear any existing pagination

    if (totalPages <= 1) return; // No pagination needed

    const maxPagesToShow = 5; // How many page numbers to show (around current page)

    const createPageButton = (label, page, disabled = false, isActive = false) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.classList.add('pagination-btn');
        if (disabled) btn.disabled = true;
        if (isActive) btn.classList.add('active-page');
        btn.addEventListener('click', () => {
            const newOffset = (page - 1) * limit;
            runSearchWithOffset(newOffset);
        });
        return btn;
    };

    // First + Prev
    paginationEl.appendChild(createPageButton('First', 1, currentPage === 1));
    paginationEl.appendChild(createPageButton('Prev', currentPage - 1, currentPage === 1));

    // Pages around current
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    for (let p = startPage; p <= endPage; p++) {
        paginationEl.appendChild(createPageButton(p, p, false, p === currentPage));
    }

    // Next + Last
    paginationEl.appendChild(createPageButton('Next', currentPage + 1, currentPage === totalPages));
    paginationEl.appendChild(createPageButton('Last', totalPages, currentPage === totalPages));
}
