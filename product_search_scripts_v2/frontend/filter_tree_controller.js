function filterTree() {
    return {
        categories: [],
        selectedOptions: [],

        async init() {
            try {
                const res = await fetch('/product_search_scripts_v2/backend/filter-data.php');
                if (!res.ok) throw new Error('Failed to load filters.');
                this.categories = await res.json();

                // Add reactive "open" property to all nested nodes
                this.applyOpenFlags(this.categories);
            } catch (error) {
                console.error('Filter tree load error:', error);
            }
        },

        applyOpenFlags(nodes) {
            nodes.forEach(node => {
                node.open = false;

                if (node.filters) {
                    node.filters.forEach(f => f.open = false);
                }

                if (node.subcategories) {
                    this.applyOpenFlags(node.subcategories);
                }
            });
        },

        submitFilters() {
            if (this.selectedOptions.length === 0) {
                alert("Please select at least one filter option.");
                return;
            }

            const query = this.selectedOptions
                .map(id => `filters[]=${encodeURIComponent(id)}`)
                .join('&');

            // Redirect to results page with filter IDs in query string
            window.location.href = `/product-search-results/?${query}`;
        }
    };
}
