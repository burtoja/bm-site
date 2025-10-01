// filter_tree_controller.js
// Alpine.js data controller for the Boilers & Machinery filter tree
// IMPORTANT behavioral rules implemented here:
// - Opening any node closes siblings and purges their selections (filters + values)
// - Changing category clears subcategory/sub-subcategory and filter values
// - Opening a FILTER heading does NOT write anything to the URL; only value changes do
// - URL is the single source of truth for deep links; POPSTATE restores UI + triggers fetch
// - Debounced querying with AbortController to avoid race conditions
// - "Clear All" returns the page to the initial empty state (sort can optionally persist)

function filterTree() {
    return {
        // ---------- STATE ----------
        categories: [],            // loaded elsewhere (or via init)
        selectedCategoryId: null,  // currently opened/active top-level category id
        isLoadingFilters: false,   // spinner for the results pane / UI

        // Other modules read this shape
        selected: {
            categoryPath: {
                categoryId: null, categoryName: null,
                subcategoryId: null, subcategoryName: null,
                subsubcategoryId: null, subsubcategoryName: null
            },
            // filters: { [filterName]: { name: string, values: string[] } }
            filters: {}
        },

        // Global (non-hierarchical) filters
        globalFilters: {
            keywords: '',
            minPrice: '',
            maxPrice: '',
            // e.g. ["new", "used"]
            condition: [],
            // 'high_to_low'|'low_to_high'
            sortOrder: 'high_to_low'
        },

        // Debounce & cancelation
        debounceTimer: null,
        currentAbort: null,

        // ---------- LIFECYCLE ----------
        // fetch categories on page load ---
        async loadAllCategories() {
            // Adjust endpoint if needed. You’ve shown filter_data.php responses before,
            // so we’ll default to that.
            const res = await fetch('filter_data.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error(`Failed to load categories: ${res.status}`);
            const data = await res.json();
            // Expecting: [{ id, name, open:false, loaded:false, filters:[], subcategories:[...] }, ...]
            this.categories = Array.isArray(data) ? data : [];
        },

        // after expanding from URL, ensure open nodes have their filters
        async ensureFiltersForOpenPath() {
            const cp = this.selected.categoryPath || {};
            if (!cp.categoryId) return;

            const cat = this.categories.find(c => String(c.id) === String(cp.categoryId));
            if (!cat) return;
            if (!cat.loaded) { cat.loaded = true; await this.loadCategoryFilters(cat).catch(()=>{}); }

            if (cp.subcategoryId && Array.isArray(cat.subcategories)) {
                const sub = cat.subcategories.find(s => String(s.id) === String(cp.subcategoryId));
                if (sub && !sub.loaded) { sub.loaded = true; await this.loadSubcategoryFilters(sub).catch(()=>{}); }

                if (cp.subsubcategoryId && Array.isArray(sub?.subcategories)) {
                    const subsub = sub.subcategories.find(s => String(s.id) === String(cp.subsubcategoryId));
                    if (subsub && !subsub.loaded) { subsub.loaded = true; await this.loadSubcategoryFilters(subsub, 'subsub').catch(()=>{}); }
                }
            }
        },

        async init() {
            try {
                await this.loadAllCategories();
            } catch (e) {
                console.error(e);
                this.categories = []; // keep UI stable
            }

            this.hydrateFromUrl(new URLSearchParams(window.location.search));
            this.expandPathFromSelected();
            await this.ensureFiltersForOpenPath();

            if (
                this.selected.categoryPath.subcategoryId ||
                this.selected.categoryPath.subsubcategoryId ||
                Object.keys(this.selected.filters).length > 0
            ) {
                this.refreshResults(0);
            }

            window.addEventListener('popstate', async () => {
                const url = new URLSearchParams(window.location.search);
                this.hydrateFromUrl(url);
                this.expandPathFromSelected();
                await this.ensureFiltersForOpenPath();
                this.refreshResults(0);
            });
        },


        // ---------- URL <-> STATE ----------
        hydrateFromUrl(url) {
            // Path
            this.selected.categoryPath = {
                categoryId: url.get('cat_id') || null,
                categoryName: url.get('cat_name') || null,
                subcategoryId: url.get('subcat_id') || null,
                subcategoryName: url.get('subcat_name') || null,
                subsubcategoryId: url.get('subsub_id') || null,
                subsubcategoryName: url.get('subsub_name') || null
            };

            // Filters (clear then repopulate)
            this.selected.filters = {};
            url.forEach((v, k) => {
                // Skip known non-filter keys
                if ([
                    'k','min_price','max_price','condition','sort',
                    'cat_id','cat_name','subcat_id','subcat_name','subsub_id','subsub_name'
                ].includes(k)) return;

                if (!this.selected.filters[k]) {
                    this.selected.filters[k] = { name: k, values: [] };
                }
                if (!this.selected.filters[k].values.includes(v)) {
                    this.selected.filters[k].values.push(v);
                }
            });

            // Globals
            this.globalFilters.keywords  = url.get('k') || '';
            this.globalFilters.minPrice  = url.get('min_price') || '';
            this.globalFilters.maxPrice  = url.get('max_price') || '';
            const conds = url.getAll('condition');
            this.globalFilters.condition = conds && conds.length ? conds : [];
            this.globalFilters.sortOrder = (url.get('sort') === 'price') ? 'low_to_high' : 'high_to_low';
        },

        expandPathFromSelected() {
            const cp = this.selected.categoryPath || {};

            // Reset open flags (do not purge here — we’re restoring)
            this.categories.forEach(cat => {
                cat.open = false;
                (cat.subcategories || []).forEach(sc => {
                    sc.open = false;
                    (sc.subcategories || []).forEach(ssc => ssc.open = false);
                });
            });

            if (!cp.categoryId) return;

            const cat = this.categories.find(c => String(c.id) === String(cp.categoryId));
            if (!cat) return;

            cat.open = true;
            this.selectedCategoryId = cat.id;

            if (cp.subcategoryId && Array.isArray(cat.subcategories)) {
                const sub = cat.subcategories.find(s => String(s.id) === String(cp.subcategoryId));
                if (sub) {
                    sub.open = true;
                    if (cp.subsubcategoryId && Array.isArray(sub.subcategories)) {
                        const subsub = sub.subcategories.find(s => String(s.id) === String(cp.subsubcategoryId));
                        if (subsub) subsub.open = true;
                    }
                }
            }
        },

        setCategoryPath({ categoryId, categoryName, subcategoryId, subcategoryName, subsubcategoryId, subsubcategoryName }) {
            this.selected.categoryPath = {
                categoryId, categoryName,
                subcategoryId, subcategoryName,
                subsubcategoryId, subsubcategoryName
            };
        },

        // ---------- PURGE HELPERS (close + clear selections under closed branches) ----------
        clearFilterValuesOwnedBy(node) {
            if (!node) return;
            const ownedNames = new Set((node.filters || []).map(f => f.name));
            if (!ownedNames.size) return;

            Object.keys(this.selected.filters).forEach(name => {
                if (ownedNames.has(name)) delete this.selected.filters[name];
            });
        },

        closeBranchDeep(node) {
            if (!node) return;
            node.open = false;
            (node.filters || []).forEach(f => f.open = false);
            this.clearFilterValuesOwnedBy(node);
            (node.subcategories || []).forEach(child => this.closeBranchDeep(child));
        },

        closeSiblingsAndPurge(parent, node) {
            if (!parent || !Array.isArray(parent.subcategories)) return;
            for (const sib of parent.subcategories) {
                if (sib !== node && sib.open) this.closeBranchDeep(sib);
            }
        },

        closeOtherCategoriesAndPurge(current) {
            for (const cat of this.categories) {
                if (cat !== current) this.closeBranchDeep(cat);
            }
        },

        // ---------- NODE TOGGLES ----------
        async toggleCategory(category) {
            const isNew = String(this.selectedCategoryId) !== String(category.id);

            if (isNew) {
                // Switching to a new category clears everything downstream
                this.closeOtherCategoriesAndPurge(category);
                category.open = true;
                this.selectedCategoryId = category.id;

                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: null, subcategoryName: null,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                if (!category.loaded) {
                    category.loaded = true; // guard double-load
                    await this.loadCategoryFilters(category);
                }

                // On category only: update URL but do not fetch (no subcategory yet)
                this.refreshResults(0, { skipFetchIfNoSubcat: true });

            } else {
                // Same category → toggle open/close
                category.open = !category.open;
                if (!category.open) {
                    // Closing a category nukes everything beneath it
                    this.closeBranchDeep(category);
                    this.selectedCategoryId = null;
                    this.setCategoryPath({
                        categoryId: null, categoryName: null,
                        subcategoryId: null, subcategoryName: null,
                        subsubcategoryId: null, subsubcategoryName: null
                    });
                    this.selected.filters = {};
                    this.refreshResults(0); // will collapse to empty state
                }
            }
        },

        async toggleSubcategory(category, subcat) {
            subcat.open = !subcat.open;

            if (subcat.open) {
                // Close siblings and purge them
                this.closeSiblingsAndPurge(category, subcat);

                // Reset deeper path (clear sub-sub + filters)
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: subcat.id, subcategoryName: subcat.name,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                if (!subcat.loaded) {
                    subcat.loaded = true;
                    await this.loadSubcategoryFilters(subcat);
                }
                this.refreshResults(0);

            } else {
                // Closing the subcategory: purge contents and update URL/UI
                this.closeBranchDeep(subcat);

                // Remove subcat + deeper from path
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: null, subcategoryName: null,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                this.refreshResults(0);
            }
        },

        async toggleSubsubcategory(category, subcat, subsub) {
            subsub.open = !subsub.open;

            if (subsub.open) {
                // Close siblings and purge
                this.closeSiblingsAndPurge(subcat, subsub);

                // Set full path; clear filters when switching leaf node
                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: subcat.id, subcategoryName: subcat.name,
                    subsubcategoryId: subsub.id, subsubcategoryName: subsub.name
                });
                this.selected.filters = {};

                if (!subsub.loaded) {
                    subsub.loaded = true;
                    await this.loadSubcategoryFilters(subsub, 'subsub');
                }

                this.refreshResults(0);

            } else {
                // Closing subsub: purge its content and clear only subsub path
                this.closeBranchDeep(subsub);

                this.setCategoryPath({
                    categoryId: category.id, categoryName: category.name,
                    subcategoryId: subcat.id, subcategoryName: subcat.name,
                    subsubcategoryId: null, subsubcategoryName: null
                });
                this.selected.filters = {};

                this.refreshResults(0);
            }
        },

        // FILTER heading (e.g., "Manufacturer") — opening should NOT touch URL
        toggleFilterHeading(filterGroup /* { name, open, options... } */) {
            filterGroup.open = !filterGroup.open;
            // No URL changes here; breadcrumb “chip” appears only when values exist
        },

        // FILTER VALUE checkbox
        toggleFilterValue(filterName, value) {
            if (!this.selected.filters[filterName]) {
                this.selected.filters[filterName] = { name: filterName, values: [] };
            }
            const arr = this.selected.filters[filterName].values;
            const idx = arr.indexOf(value);

            if (idx === -1) {
                arr.push(value); // checking
            } else {
                arr.splice(idx, 1); // unchecking
                if (arr.length === 0) delete this.selected.filters[filterName]; // drop empty group
            }

            this.onSelectionChange();
        },

        // ---------- SELECTION CHANGES ----------
        onSelectionChange() {
            this.refreshResults(0);
        },

        // Debounced fetch + URL sync
        refreshResults(offset = 0, opts = {}) {
            const { skipFetchIfNoSubcat = false } = opts;

            // Cancel in-flight
            if (this.currentAbort) {
                try { this.currentAbort.abort(); } catch {}
                this.currentAbort = null;
            }
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = null;
            }

            // Always sync URL first
            const params = buildParamsFromSelections({
                categories: this.categories,
                selected:  this.selected,
                globals:   this.globalFilters
            });
            window.history.replaceState({}, '', `?${params.toString()}`);

            // If the user has only picked a category (no subcat), keep results empty
            const cp = this.selected.categoryPath || {};
            const hasBrowseContext = !!(cp.subcategoryId || cp.subsubcategoryId);

            if (skipFetchIfNoSubcat && !hasBrowseContext && Object.keys(this.selected.filters).length === 0) {
                // show empty-state hint in your results pane (no fetch)
                return;
            }

            // Debounce and fetch
            this.isLoadingFilters = true;
            this.debounceTimer = setTimeout(() => {
                runSearchWithOffset(offset); // keep your existing signature
                this.isLoadingFilters = false;
            }, 250);
        },

        // Called by “Search” button (desktop/mobile)
        submitFilters() {
            this.refreshResults(0);
            // If you close the drawer on mobile, do it here.
        },

        // ---------- CLEAR ALL ----------
        clearAll() {
            // Nuke selections
            this.selectedCategoryId = null;
            this.selected.categoryPath = {
                categoryId: null, categoryName: null,
                subcategoryId: null, subcategoryName: null,
                subsubcategoryId: null, subsubcategoryName: null
            };
            this.selected.filters = {};

            // Optionally keep sort; wipe others
            this.globalFilters = {
                keywords: '',
                minPrice: '',
                maxPrice: '',
                condition: [],
                sortOrder: this.globalFilters.sortOrder // persist sort
            };

            // Close everything and purge
            this.categories.forEach(cat => this.closeBranchDeep(cat));

            // Sync URL with only sort (optional)
            const params = new URLSearchParams();
            params.set('sort', this.globalFilters.sortOrder === 'low_to_high' ? 'price' : '-price');
            window.history.replaceState({}, '', `?${params.toString()}`);

            this.refreshResults(0);
        },

        // ---------- DATA LOADING (stubs to match your wiring) ----------
        async loadCategoryFilters(category) {
            // If API returns filters at category level, fetch & attach:
            // const data = await fetch(...).then(r => r.json());
            // category.filters = data.filters || [];
            // category.subcategories = data.subcategories || category.subcategories;
        },

        async loadSubcategoryFilters(node /* subcat or subsub */, level = 'subcat') {
            // Fetch filters/options for this node if you lazy-load them
            // const data = await fetch(...).then(r => r.json());
            // node.filters = data.filters || node.filters;
            // node.subcategories = data.subcategories || node.subcategories;
        },

        // ---------- UTILITIES YOU MAY ALREADY HAVE IN TEMPLATE ----------
        isValueChecked(filterName, value) {
            return !!(this.selected.filters[filterName] &&
                this.selected.filters[filterName].values.includes(value));
        },

        filterHasSelections(filterName) {
            return !!(this.selected.filters[filterName] &&
                this.selected.filters[filterName].values.length);
        },

        // Example: called from template label clicks to set path quickly
        setCategoryNodePath(category, subcat = null, subsub = null) {
            this.setCategoryPath({
                categoryId: category ? category.id : null,
                categoryName: category ? category.name : null,
                subcategoryId: subcat ? subcat.id : null,
                subcategoryName: subcat ? subcat.name : null,
                subsubcategoryId: subsub ? subsub.id : null,
                subsubcategoryName: subsub ? subsub.name : null
            });
        }
    };
}

window.filterTree = filterTree;
