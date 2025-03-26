function renderResults(results) {
    const container = document.getElementById('search-results');
    container.innerHTML = '';

    if (!results || results.length === 0) {
        container.innerHTML = '<p>No results found.</p>';
        return;
    }

    results.forEach(item => {
        const html = `
            <div class="result-item">
                <img src="${item.image}" alt="${item.title}" />
                <h4><a href="${item.url}" target="_blank">${item.title}</a></h4>
                <p>$${item.price}</p>
            </div>
        `;
        container.innerHTML += html;
    });
}
