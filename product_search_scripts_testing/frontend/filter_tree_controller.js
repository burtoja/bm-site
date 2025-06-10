
function filterTree() {
    return {
        categories: [],
        selectedOptions: [],
        selectedCategoryId: null,
        globalFilters: {
            condition: [],
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

        toggleCategory(category) {
            if (this.selectedCategoryId !== category.id) {
                // A new category is selected, so reset others
                this.categories.forEach(cat => {
                    if (cat.id !== category.id) {
                        cat.open = false;
                        cat.filters?.forEach(f => {
                            f.options?.forEach(o => o.checked = false);
                        });
                        cat.subcategories?.forEach(sub => {
                            sub.open = false;
                            sub.filters?.forEach(f => {
                                f.options?.forEach(o => o.checked = false);
                            });
                        });
                    }
                });

                category.open = true;
                this.selectedCategoryId = category.id;
            } else {
                // Clicking the same category again toggles it closed
                category.open = !category.open;
                if (!category.open) {
                    this.selectedCategoryId = null;
                    category.filters?.forEach(f => {
                        f.options?.forEach(o => o.checked = false);
                    });
                    category.subcategories?.forEach(sub => {
                        sub.open = false;
                        sub.filters?.forEach(f => {
                            f.options?.forEach(o => o.checked = false);
                        });
                    });
                }
            }
        },


        submitFilters() {
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

            // Redirect to search results page
            //window.location.href = `/product-search-results/?${query.toString()}`;
            window.history.replaceState({}, '', `?${query.toString()}`);
            runSearchWithOffset();

        }
    };
}
