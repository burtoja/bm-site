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
        initialized: false,

        async init() {
            if (this.initialized) {
                console.warn('init() already called — skipping');
                return;
            }
            this.initialized = true;
            console.log('Running init() at', new Date().toISOString());
            try {
                const res = await fetch('/product_search_scripts_testing/backend/filter_data.php');
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
                node.loaded = false; // for lazy-loading
                if (node.filters) {
                    node.filters.forEach(f => f.open = false);
                }
                if (node.subcategories) {
                    this.applyOpenFlags(node.subcategories);
                }
            });
        },

        async toggleCategory(category) {
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

                if (!category.loaded) {
                    await this.loadCategoryFilters(category);
                }
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

        async loadCategoryFilters(category) {
            if (category.loaded) return;
            try {
                const res = await fetch(`/product_search_scripts_testing/backend/load_filters.php?category_id=${category.id}`);
                const data = await res.json();
                category.filters = data.filters;
                category.loaded = true;

            } catch (error) {
                console.error('Failed to load category filters:', error);
            }
        },

        async loadFiltersForNode(node, paramName, id) {
            if (node.loaded) return;
            try {
                const res = await fetch(`/product_search_scripts_testing/backend/load_filters.php?${paramName}=${id}`);
                const data = await res.json();
                node.filters = data.filters;
                node.loaded = true;
            } catch (error) {
                console.error(`Failed to load filters for ${paramName}=${id}:`, error);
            }
        },

        async submitFilters() {
            this.isLoadingFilters = true;

            // 🔁 Make sure filters are loaded for all categories with selected options
            const nodesToScan = [...this.categories];

            // Helper: Flatten and collect all nodes (categories, subcats, subsubs)
            function collectAllNodes(nodes) {
                const all = [];
                for (const node of nodes) {
                    all.push(node);
                    if (Array.isArray(node.subcategories)) {
                        all.push(...collectAllNodes(node.subcategories));
                    }
                }
                return all;
            }

            const allNodes = collectAllNodes(nodesToScan);

            // 🔄 Load filters for any node that might contain selected option IDs
            for (const node of allNodes) {
                if (!node.loaded && node.filters === undefined) {
                    if (node.hasOwnProperty('subsubcategory_id')) {
                        await this.loadSubcategoryFilters(node, 'subsub');
                    } else if (node.hasOwnProperty('subcategory_id') || node.subcategories) {
                        await this.loadSubcategoryFilters(node);
                    } else {
                        await this.loadCategoryFilters(node);
                    }
                }
            }

            // ✅ Now build the query
            const q = buildQueryFromSelections({
                categories: this.categories,
                selectedOptions: this.selectedOptions,
                globalFilters: this.globalFilters
            });

            console.log('Built q:', q);

            const sort = this.globalFilters.sortOrder === 'low_to_high' ? 'price' : '-price';
            const query = new URLSearchParams();
            query.set('q', q);
            query.set('sort', sort);

            if (this.globalFilters.minPrice) query.set('min_price', this.globalFilters.minPrice);
            if (this.globalFilters.maxPrice) query.set('max_price', this.globalFilters.maxPrice);
            if (this.globalFilters.condition.length > 0) {
                this.globalFilters.condition.forEach(c => query.append('condition', c));
            }

            console.log('Final query string:', query.toString());
            window.history.replaceState({}, '', `?${query.toString()}`);
            runSearchWithOffset();

            this.isLoadingFilters = false;
        }


    };
}
