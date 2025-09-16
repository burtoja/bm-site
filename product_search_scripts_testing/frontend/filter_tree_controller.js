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

        // track active branch and options
        activeSubcategoryId: null,
        activeSubsubcategoryId: null,
        optionIndex: {},
        filterNameById: {},

        async init() {
            if (this.initialized) {
                return;
            }
            this.initialized = true;
            try {
                const res = await fetch('/product_search_scripts_testing/backend/filter_data.php');
                if (!res.ok) throw new Error('Failed to load filters.');
                this.categories = await res.json();
                this.applyOpenFlags(this.categories);
            } catch (error) {
                console.error('Filter tree load error:', error);
            }
        },

        // Called after any fetch of filters for a node
        indexFilters(node) {
            (node.filters || []).forEach(f => {
                this.filterNameById[f.id] = f.name;
                (f.options || []).forEach(o => {
                    this.optionIndex[o.id] = { id: o.id, value: o.value, filterId: f.id, filterName: f.name };
                });
            });
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

        /* Summary helpers to display selections */

        hasAnySelection() {
            return !!(this.selectedCategoryId || this.activeSubcategoryId || this.activeSubsubcategoryId || (this.selectedOptions?.length));
        },

        breadcrumb() {
            const names = [];
            const cat = this.categories.find(c => c.id === this.selectedCategoryId);
            if (cat) names.push(cat.name);

            const findSubcat = (id) => {
                if (!id || !cat) return null;
                // search depth-1
                let sc = (cat.subcategories || []).find(s => s.id === id);
                if (sc) return sc;
                // or any child of those
                for (const s of (cat.subcategories || [])) {
                    const hit = (s.subcategories || []).find(ss => ss.id === id);
                    if (hit) return hit;
                }
                return null;
            };

            const sub = findSubcat(this.activeSubcategoryId);
            if (sub) names.push(sub.name);

            const subsub = findSubcat(this.activeSubsubcategoryId);
            if (subsub && (!sub || subsub.id !== sub.id)) names.push(subsub.name);

            return names;
        },

        groupedSelections() {
            // group selectedOptions by filterName using optionIndex
            const groups = {};
            (this.selectedOptions || []).forEach(id => {
                const meta = this.optionIndex[id];
                if (!meta) return;
                const key = meta.filterName || this.filterNameById[meta.filterId] || 'Filter';
                if (!groups[key]) groups[key] = [];
                groups[key].push({ id, value: meta.value });
            });
            // sort options alphabetically inside each group (optional)
            return Object.keys(groups).sort().map(name => ({
                name,
                options: groups[name].sort((a,b)=> a.value.localeCompare(b.value))
            }));
        },

        removeOption(id) {
            this.selectedOptions = this.selectedOptions.filter(v => v !== id);
        },

        clearAll() {
            this.selectedOptions = [];
            this.activeSubsubcategoryId = null;
            this.activeSubcategoryId = null;
            // keep selectedCategoryId so the user knows where they are,
            // remove it too if you prefer:
            // this.selectedCategoryId = null;
        },

        /* END Summary Helpers for current selections display */

        async toggleCategory(category) {
            if (this.selectedCategoryId !== category.id) {
                // A new category is selected
                this.categories.forEach(cat => {
                    if (cat.id !== category.id) {
                        cat.open = false;
                    }
                });

                category.open = true;
                this.selectedCategoryId = category.id;

                if (!category.loaded) {
                    category.loaded = true;  // Set this BEFORE the await to avoid race condition
                    await this.loadCategoryFilters(category);
                }
            } else {
                // Toggling same category closed
                category.open = !category.open;
                if (!category.open) {
                    this.selectedCategoryId = null;
                }
            }
        },


        async loadCategoryFilters(category) {
            try {
                const res = await fetch(`/product_search_scripts_testing/backend/load_filters.php?category_id=${category.id}`);
                const data = await res.json();
                category.filters = data.filters;
            } catch (error) {
                category.loaded = false; // allow retry if it fails
            }
        }
        ,

        async loadSubcategoryFilters(subcat, level = 'subcategory') {
            subcat.loaded = true;

            let paramName = 'subcategory_id';
            if (level === 'subsub') paramName = 'subcategory_id';

            try {
                const res = await fetch(`/product_search_scripts_testing/backend/load_filters.php?${paramName}=${subcat.id}`);
                const data = await res.json();

                if (data.filters) {
                    subcat.filters = data.filters;
                } else {
                    subcat.filters = [];
                }
                this.indexFilters(subcat);
            } catch (e) {
                console.error("Failed to load filters for " + level + ":", e);
                subcat.filters = [];
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

            const sort = this.globalFilters.sortOrder === 'low_to_high' ? 'price' : '-price';
            const query = new URLSearchParams();
            query.set('q', q);
            query.set('sort', sort);

            if (this.globalFilters.minPrice) query.set('min_price', this.globalFilters.minPrice);
            if (this.globalFilters.maxPrice) query.set('max_price', this.globalFilters.maxPrice);
            if (this.globalFilters.condition.length > 0) {
                this.globalFilters.condition.forEach(c => query.append('condition', c));
            }

            window.history.replaceState({}, '', `?${query.toString()}`);
            runSearchWithOffset();

            if (window.innerWidth < 768) {
                const outerScope = document.querySelector('[x-data]').__x.$data;
                outerScope.showFilters = false;
            }


            this.isLoadingFilters = false;
        }

    };
}

window.filterTree = filterTree;
