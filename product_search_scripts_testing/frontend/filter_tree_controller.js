function filterTree() {
    return {
        categories: [],
        selectedOptions: [],
        selectedCategoryId: null,
        isLoadingFilters: false,
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
            console.log('--loadCategoryFilters() triggered for:', category.name);
            try {
                const res = await fetch(`/product_search_scripts_testing/backend/load_filters.php?category_id=${category.id}`);
                const data = await res.json();

                console.log('Filters loaded for category:', category.name);
                console.log(data.filters);

                category.filters = data.filters;
                category.loaded = true;

            } catch (error) {
                console.error('Failed to load category filters:', error);
            }
        },

        async loadSubcategoryFilters(subcat, type = 'subcat') {
            if (subcat.loaded) return;
            const param = type === 'subsub' ? 'subsubcategory_id' : 'subcategory_id';
            console.log(`--loadSubcategoryFilters() triggered for ${type}:`, subcat.name);

            try {
                const res = await fetch(`/product_search_scripts_testing/backend/load_filters.php?${param}=${subcat.id}`);
                const data = await res.json();

                console.log(`Filters loaded for ${type}:`, subcat.name);
                console.log(data.filters);

                subcat.filters = data.filters;
                subcat.loaded = true;
            } catch (error) {
                console.error(`Failed to load filters for ${type}:`, error);
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

            // Normalize selected option IDs to string for matching
            const selectedSet = new Set(this.selectedOptions.map(id => String(id)));

            // Recursively walk the tree and ensure filters are loaded where needed
            async function preloadFiltersForSelectedOptions(nodes, context) {
                for (const node of nodes) {
                    // If node has filters and is not loaded, load them
                    if (!node.loaded && node.filters === undefined) {
                        if (context === 'subsub') {
                            await this.loadSubcategoryFilters(node, 'subsub');
                        } else if (context === 'subcat') {
                            await this.loadSubcategoryFilters(node);
                        } else {
                            await this.loadCategoryFilters(node);
                        }
                    }

                    // If filters are now loaded, check if they include a selected option
                    if (node.filters) {
                        const found = node.filters.some(filter =>
                            filter.options?.some(opt => selectedSet.has(String(opt.id)))
                        );

                        if (found) continue; // already has the match
                    }

                    // Recurse into subcategories
                    if (Array.isArray(node.subcategories)) {
                        const level = context === 'category' ? 'subcat' : 'subsub';
                        await preloadFiltersForSelectedOptions.call(this, node.subcategories, level);
                    }
                }
            }

            await preloadFiltersForSelectedOptions.call(this, this.categories, 'category');

            // Build the query after all matching filters are loaded
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

window.filterTree = filterTree;
