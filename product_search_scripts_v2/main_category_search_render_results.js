/**
 * Renders the results from the api call in the page of the filters
 * @param data
 */
function renderResults(data) {
    const container = document.getElementById('search-results');
    container.innerHTML = '';

    const items = data.itemSummaries || [];

    if (items.length === 0) {
        container.innerHTML = '<p>No results found.</p>';
        return;
    }

    items.forEach(item => {
        const html = `
            <div class="result-card">
                <a href="${item.itemWebUrl}" target="_blank">
                    <div class="result-image">
                        <img src="${item.image?.imageUrl || ''}" alt="${item.title}" style="width:100%; height:auto;">
                    </div>
                    <h5>${item.title}</h5>
                    <p><strong>$${item.price.value}</strong></p>
                </a>
            </div>
        `;
        container.innerHTML += html;
    });
}
