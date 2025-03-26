async function fetchSearchResults(apiUrl) {
    try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error('Request failed');
        const data = await response.json();

        renderResults(data); // your function to display results
    } catch (err) {
        console.error('Search Error:', err);
        alert('Search failed. Please try again.');
    }
}
