// filter_tree_controller.js — SIMPLE, CLEAN IMPLEMENTATION

function filterTree() {
    return {
        // ---- state ----
        categories: [],
        selectedCategoryId: null,
        isLoadingFilters: false,

        selected: {
            categoryPath: {
                categoryId: null, categoryName: null,
                subcategoryId: null, subcategoryName: null,
                subsubcategoryId: null, subsubcategoryName: null
            },
            // { [filterName]: { name, values: [string] } }
            filters: {}
        },

        globalFilters: {
            keywords: '',
            minPrice: '',
            maxPrice: '',
            condition: [],
            sortOrder: 'high_to_low'
        },

        // ---- lifecycle ----
        async init() {
            await this.loadAllCategories();
            // Optional: hydrate from URL if you want deep-link restore on day 1.
            this.hydrateFromUrl(new URLSearchParams(window.location.search));
            this.expandPathFromSelected();

            // Initial fetch only if we have a subcategory (or sub-sub) selected
            const cp = this.selected.categoryPath;
            if (cp.subcategoryId || cp.subsubcategoryId) {
                this.syncUrl(); this.runSearch(0);
            }

            window.addEventListener('popstate', () => {
                this.hydrateFromUrl(new URLSearchParams(window.location.search));
                this.expandPathFromSelected();
                const cp2 = this.selected.categoryPath;
                if (cp2.subcategoryId || cp2.subsubcategoryId) this.runSearch(0);
            });
        },

        async loadAllCategories() {
            try {
                const res = await fetch('filter_data.php', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Failed to load categories');
                const data = await res.json();
                // Normalize flags
                this.categories = (Array.isArray(data) ? data : []).map(cat => ({
                    ...cat, open: !!cat.open,
                    filters: (cat.filters || []).map(f => ({ ...f, open: !!f.open })),
                    subcategories: (cat.subcategories || []).map(sc => ({
                        ...sc, open: !!sc.open,
                        filters: (sc.filters || []).map(f => ({ ...f, open: !!f.open })),
                        subcategories: (sc.subcategories || []).map(ssc => ({
                            ...ssc, open: !!ssc.open,
                            filters: (ssc.filters || []).map(f => ({ ...f, open: !!f.open }))
                        }))
                    }))
                }));
            } catch (e) {
                console.error(e);
                this.categories = [];
            }
        },

        // ---- path helpers ----
        setCategoryPath({ categoryId, categoryName, subcategoryId, subcategoryName, subsubcategoryId, subsubcategoryName }) {
            this.selected.categoryPath = { categoryId, categoryName, subcategoryId, subcategoryName, subsubcategoryId, subsubcategoryName };
        },

        expandPathFromSelected() {
            const cp = this.selected.categoryPath;
            this.categories.forEach(c => {
                c.open = (String(c.id) === String(cp.categoryId));
                (c.subcategories || []).forEach(sc => {
                    sc.open = c.open && (String(sc.id) === String(cp.subcategoryId));
                    (sc.subcategories || []).forEach(ssc => {
                        ssc.open = sc.open && (String(ssc.id) === String(cp.subsubcategoryId));
                    });
                });
            });
            this.selectedCategoryId = cp.categoryId ? Number(cp.categoryId) : null;
        },

        // ---- purge/close logic ----
        clearFilterValuesOwnedBy(node) {
            if (!node) return;
            const owned = new Set((node.filters || []).map(f => f.name));
            if (!owned.size) return;
            Object.keys(this.selected.filters).forEach(name => { if (owned.has(name)) delete this.selected.filters[name]; });
        },

        closeBranchDeep(node) {
            if (!node) return;
            node.open = false;
            (node.filters || []).forEach(f => f.open = false);
            this.clearFilterValuesOwnedBy(node);
            (node.subcategories || []).forEach(ch => this.closeBranchDeep(ch));
        },

        closeSiblings(parent, node) {
            const arr = parent ? (parent.subcategories || []) : this.categories;
            arr.forEach(sib => { if (sib !== node && sib.open) this.closeBranchDeep(sib); });
        },

        // ---- node toggles ----
        async toggleCategory(category) {
            const isOpeningNew = String(this.selectedCategoryId) !== String(category.id);

            if (isOpeningNew) {
                // close other categories and purge
                this.categories.forEach(cat => { if (cat !== category) this.closeBranchDeep(cat); });
                category.open = true;
                this.selectedCategoryId = category.id;

                // reset path below category + filters
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: null, subcategoryName: null,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                // URL should show only the category (no fetch)
                this.syncUrl();
            } else {
                // toggle close
                category.open = !category.open;
                if (!category.open) {
                    this.closeBranchDeep(category);
                    this.selectedCategoryId = null;
                    this.setCategoryPath({
                        categoryId: null, categoryName: null,
                        subcategoryId: null, subcategoryName: null,
                        subsubcategoryId: null, subsubcategoryName: null
                    });
                    this.selected.filters = {};
                    this.syncUrl();
                    // No fetch; empty state
                }
            }
        },

        async toggleSubcategory(category, subcat) {
            subcat.open = !subcat.open;

            if (subcat.open) {
                // close siblings under same category
                this.closeSiblings(category, subcat);

                // switching subcategory clears deeper path + filters
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: subcat.id, subcategoryName: subcat.name,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                this.syncUrl();
                this.runSearch(0); // baseline fetch for subcategory
            } else {
                // closing subcategory clears its subtree + filters and path
                this.closeBranchDeep(subcat);
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: null, subcategoryName: null,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                this.syncUrl();
                // empty state after closing subcategory
            }
        },

        async toggleSubsubcategory(category, subcat, subsub) {
            subsub.open = !subsub.open;

            if (subsub.open) {
                this.closeSiblings(subcat, subsub);
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: subcat.id, subcategoryName: subcat.name,
                    subsubcategoryId: subsub.id, subsubcategoryName: subsub.name
                });
                this.selected.filters = {};

                this.syncUrl();
                this.runSearch(0);
            } else {
                this.closeBranchDeep(subsub);
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: subcat.id, subcategoryName: subcat.name,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                this.syncUrl();
                this.runSearch(0); // you can omit if you prefer empty state
            }
        },

        // ---- filters ----
        toggleFilterHeading(filterGroup) {
            filterGroup.open = !filterGroup.open; // opening a heading does NOT touch URL
        },

        toggleFilter(filterName, value, checked) {
            if (!this.selected.filters[filterName]) this.selected.filters[filterName] = { name: filterName, values: [] };
            const vals = this.selected.filters[filterName].values;
            const idx = vals.indexOf(value);

            if (checked && idx === -1) vals.push(value);
            if (!checked && idx !== -1) vals.splice(idx, 1);
            if (this.selected.filters[filterName] && this.selected.filters[filterName].values.length === 0) delete this.selected.filters[filterName];

            // breadcrumb + URL + maybe fetch
            this.onSelectionChange();
        },

        isChecked(filterName, value) {
            return !!(this.selected.filters[filterName] && this.selected.filters[filterName].values.includes(value));
        },

        // ---- selection change ----
        onSelectionChange() {
            this.syncUrl();
            const cp = this.selected.categoryPath;
            if (cp.subcategoryId || cp.subsubcategoryId) this.runSearch(0);
            // If only a category is selected, we keep results empty.
        },

        // ---- URL sync ----
        syncUrl() {
            const p = new URLSearchParams();

            // path
            const cp = this.selected.categoryPath;
            if (cp.categoryId) { p.set('cat_id', cp.categoryId); if (cp.categoryName) p.set('cat_name', cp.categoryName); }
            if (cp.subcategoryId) { p.set('subcat_id', cp.subcategoryId); if (cp.subcategoryName) p.set('subcat_name', cp.subcategoryName); }
            if (cp.subsubcategoryId) { p.set('subsub_id', cp.subsubcategoryId); if (cp.subsubcategoryName) p.set('subsub_name', cp.subsubcategoryName); }

            // globals (optional – include what you actually use)
            if (this.globalFilters.keywords) p.set('k', this.globalFilters.keywords);
            if (this.globalFilters.minPrice) p.set('min_price', this.globalFilters.minPrice);
            if (this.globalFilters.maxPrice) p.set('max_price', this.globalFilters.maxPrice);
            (this.globalFilters.condition || []).forEach(c => p.append('condition', c));
            p.set('sort', this.globalFilters.sortOrder === 'low_to_high' ? 'price' : '-price');

            // filters (by name → multiple values)
            Object.values(this.selected.filters).forEach(group => {
                (group.values || []).forEach(v => p.append(group.name, v));
            });

            window.history.replaceState({}, '', `?${p.toString()}`);
        },

        hydrateFromUrl(p) {
            this.setCategoryPath({
                categoryId: p.get('cat_id') || null,
                categoryName: p.get('cat_name') || null,
                subcategoryId: p.get('subcat_id') || null,
                subcategoryName: p.get('subcat_name') || null,
                subsubcategoryId: p.get('subsub_id') || null,
                subsubcategoryName: p.get('subsub_name') || null
            });

            this.selected.filters = {};
            p.forEach((v, k) => {
                if (['k','min_price','max_price','condition','sort','cat_id','cat_name','subcat_id','subcat_name','subsub_id','subsub_name'].includes(k)) return;
                if (!this.selected.filters[k]) this.selected.filters[k] = { name: k, values: [] };
                if (!this.selected.filters[k].values.includes(v)) this.selected.filters[k].values.push(v);
            });

            this.globalFilters.keywords = p.get('k') || '';
            this.globalFilters.minPrice = p.get('min_price') || '';
            this.globalFilters.maxPrice = p.get('max_price') || '';
            this.globalFilters.condition = p.getAll('condition') || [];
            this.globalFilters.sortOrder = (p.get('sort') === 'price') ? 'low_to_high' : 'high_to_low';
        },

        // ---- search trigger (kept simple) ----
        runSearch(offset) {
            // Call through if your page defines it; otherwise no-op
            if (typeof window.runSearchWithOffset === 'function') {
                window.runSearchWithOffset(offset);
            }
        },

        // ---- breadcrumb, chips, clear ----
        hasAnySelection() {
            const cp = this.selected.categoryPath;
            const anyPath = cp.categoryId || cp.subcategoryId || cp.subsubcategoryId;
            const anyFilters = Object.keys(this.selected.filters).length > 0;
            return !!(anyPath || anyFilters);
        },

        breadcrumb() {
            const cp = this.selected.categoryPath;
            const parts = [];
            if (cp.categoryName) parts.push(cp.categoryName);
            if (cp.subcategoryName) parts.push(cp.subcategoryName);
            if (cp.subsubcategoryName) parts.push(cp.subsubcategoryName);
            return parts;
        },

        groupedSelections() {
            return Object.values(this.selected.filters).map(g => ({
                name: g.name,
                options: (g.values || []).map((v, i) => ({ id: `${g.name}:${v}:${i}`, value: v }))
            }));
        },

        removeOption(optId) {
            // optId is `${name}:${value}:${i}`
            const [name, ...rest] = optId.split(':');
            const value = rest.slice(0, rest.length - 1).join(':'); // in case value contains ':'
            const group = this.selected.filters[name];
            if (!group) return;
            group.values = group.values.filter(v => v !== value);
            if (!group.values.length) delete this.selected.filters[name];
            this.onSelectionChange();
        },

        clearAll() {
            // close everything
            this.categories.forEach(cat => this.closeBranchDeep(cat));

            // reset selection
            this.selectedCategoryId = null;
            this.setCategoryPath({
                categoryId: null, categoryName: null,
                subcategoryId: null, subcategoryName: null,
                subsubcategoryId: null, subsubcategoryName: null
            });
            this.selected.filters = {};

            // keep sort but clear other globals (optional)
            this.globalFilters.keywords = '';
            this.globalFilters.minPrice = '';
            this.globalFilters.maxPrice = '';
            this.globalFilters.condition = [];

            // URL + empty results
            this.syncUrl();
            // no fetch (empty state)
        }
    };
}

window.filterTree = filterTree;
