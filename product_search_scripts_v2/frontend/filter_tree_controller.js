
function filterTree() {
    return {
        categories: [],
        selectedOptions: [],
        globalFilters: {
            condition: "",
            priceRange: "any",
            minPrice: "",
            maxPrice: "",
            sortOrder: "high_to_low"
        },

        async init() {
            try {
                const res = await fetch('/product_search_scripts_v2/backend/filter-data.php');
                if (!res.ok) throw new Error('Failed to load filters.');
                this.categories = await res.json();
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
            const selectedIds = this.selectedOptions.map(id => id.replace(/^opt_/, ''));
            const params = selectedIds.map(id => `filters[]=${encodeURIComponent(id)}`);

            // Add global filter values
            if (this.globalFilters.condition) {
                params.push(`condition=${encodeURIComponent(this.globalFilters.condition)}`);
            }

            if (this.globalFilters.priceRange === 'under100') {
                params.push('max_price=100');
            } else if (this.globalFilters.priceRange === 'custom') {
                if (this.globalFilters.minPrice) {
                    params.push(`min_price=${encodeURIComponent(this.globalFilters.minPrice)}`);
                }
                if (this.globalFilters.maxPrice) {
                    params.push(`max_price=${encodeURIComponent(this.globalFilters.maxPrice)}`);
                }
            }

            if (this.globalFilters.sortOrder) {
                const sortVal = this.globalFilters.sortOrder === 'low_to_high' ? 'price_asc' : 'price_desc';
                params.push(`sort_order=${sortVal}`);
            }

            // Collect selections to build endpoint q parameter
            const q = buildQueryFromSelections({
                categories: this.categories,
                selectedOptions: this.selectedOptions,
                globalFilters: this.globalFilters
            });

            const sort = this.globalFilters.sortOrder === 'low_to_high' ? 'price' : '-price';

            const query = new URLSearchParams();
            query.set('q', q);
            query.set('sort', sort);

            if (this.globalFilters.minPrice) query.set('min_price', this.globalFilters.minPrice);
            if (this.globalFilters.maxPrice) query.set('max_price', this.globalFilters.maxPrice);
            if (this.globalFilters.condition.length > 0) {
                this.globalFilters.condition.forEach(c => query.append('condition', c));
            }

            window.location.href = `/product-search-results/?${query.toString()}`;

            // const q = buildQueryFromSelections({
            //     categories: this.categories,
            //     selectedOptions: this.selectedOptions,
            //     globalFilters: this.globalFilters
            // });
            //
            // const finalUrl = `/product-search-results/?q=${encodeURIComponent(q)}`;
            // window.location.href = finalUrl;
            //
            //
            // // Redirect with final query
            // const query = params.join('&');
            // window.location.href = `/product-search-results/?${query}`;
        }
    };
}
