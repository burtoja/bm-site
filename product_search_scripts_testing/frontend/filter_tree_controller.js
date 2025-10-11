function filterTree() {
    return {
        categories: [],
        //selectedOptions: [],
        selected: {
            categoryPath: {
                categoryId: null, categoryName: null,
                subcategoryId: null, subcategoryName: null,
                subsubcategoryId: null, subsubcategoryName: null
            },
            filters: {},

            chosen: {},
        },

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

        //track active branch and options
        activeSubcategoryId: null,
        activeSubsubcategoryId: null,
        optionIndex: {},
        filterNameById: {},

        async init() {
            const url = new URLSearchParams(window.location.search);

            // Category path names (for breadcrumb)
            this.selected.categoryPath = {
                categoryId:       url.get('cat_id')       || null,
                categoryName:     url.get('cat_name')     || null,
                subcategoryId:    url.get('subcat_id')    || null,
                subcategoryName:  url.get('subcat_name')  || null,
                subsubcategoryId: url.get('subsub_id')    || null,
                subsubcategoryName: url.get('subsub_name')|| null
            };

            // Filters (flt[Name][]=Val)
            const entries = Array.from(url.keys()).filter(k => k.startsWith('flt['));
            entries.forEach(key => {
                // key looks like: flt[Filter Name][]
                const name = decodeURIComponent(key).slice(4, -2); // strip 'flt[' and ']'
                const vals = url.getAll(key);
                if (!this.selected.filters[name]) {
                    this.selected.filters[name] = { name, values: [] };
                }
                // merge + dedupe
                this.selected.filters[name].values = Array.from(new Set([
                    ...this.selected.filters[name].values, ...vals
                ]));
            });

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

        /* =========================
 * BREADCRUMB (uses structured categoryPath)
 * ========================= */
        breadcrumb() {
            const trail = [];
            const cp = this.selected?.categoryPath || {};
            if (cp.categoryName)       trail.push(cp.categoryName);
            if (cp.subcategoryName)    trail.push(cp.subcategoryName);
            if (cp.subsubcategoryName) trail.push(cp.subsubcategoryName);
            return trail;
        },

        /* =======================================
         * GROUPED SELECTIONS (for chips block)
         * Returns: [{ name, options: [{id, value}] }]
         * ======================================= */
        groupedSelections() {
            const groups = [];
            const src = this.selected?.filters || {};
            Object.keys(src).forEach((name) => {
                const vals = Array.isArray(src[name]?.values) ? src[name].values : [];
                if (vals.length) {
                    groups.push({
                        name,
                        options: vals
                            .slice()
                            .sort((a, b) => a.localeCompare(b))
                            .map(v => ({ id: `${name}::${v}`, value: v }))
                    });
                }
            });
            // sort groups by name (optional)
            return groups.sort((a, b) => a.name.localeCompare(b.name));
        },

        /* ==========================================
         * REMOVE ONE CHIP (id looks like "Filter Name::Value")
         * ========================================== */
        removeOption(id) {
            const pos = id.indexOf('::');
            if (pos === -1) return;
            const filterName = id.slice(0, pos);
            const value = id.slice(pos + 2);

            if (!this.selected.filters[filterName]) return;

            this.selected.filters[filterName].values =
                this.selected.filters[filterName].values.filter(v => v !== value);

            if (this.selected.filters[filterName].values.length === 0) {
                delete this.selected.filters[filterName];
            }

            // reflect in URL + re-run search
            this.onSelectionChange();
        },

        /* ==========================================
         * Any selection? (drives x-show on the block)
         * ========================================== */
        hasAnySelection() {
            const cp = this.selected?.categoryPath || {};
            const hasTrail = !!(cp.categoryName || cp.subcategoryName || cp.subsubcategoryName);
            const hasFilters = Object.values(this.selected?.filters || {})
                .some(g => Array.isArray(g.values) && g.values.length > 0);
            return hasTrail || hasFilters;
        },

        /* ==========================================
         * Clear everything and refresh
         * ========================================== */
        clearAll() {
            this.selected.categoryPath = {
                categoryId: null, categoryName: null,
                subcategoryId: null, subcategoryName: null,
                subsubcategoryId: null, subsubcategoryName: null
            };
            this.selected.filters = {};
            this.globalFilters = { ...this.globalFilters, keywords: '', minPrice: '', maxPrice: '' };

            // wipe URL; keep sort if you like
            const params = new URLSearchParams();
            params.set('sort', this.globalFilters.sortOrder === 'low_to_high' ? 'price' : '-price');

            window.history.replaceState({}, '', `?${params.toString()}`);
            runSearchWithOffset(0);
        },


        /* Summary helpers to display selections */

        setActiveBranch(subcat, subsub = null) {
            // Track which branch is active
            this.activeSubcategoryId = subcat ? subcat.id : null;
            this.activeSubsubcategoryId = subsub ? subsub.id : null;

            // Find parent category of subcat
            const cat = this.categories.find(c =>
                Array.isArray(c.subcategories) && c.subcategories.some(s => s.id === (subcat ? subcat.id : null))
            );
            if (cat && !this.selectedCategoryId) {
                this.selectedCategoryId = cat.id;
            }

            // If focusing a SUBCATEGORY: close sibling subcats and purge their selections (and their sub-subs)
            if (subsub == null && cat) {
                cat.subcategories.forEach(sibling => {
                    if (sibling.id !== subcat.id) {
                        if (sibling.open) sibling.open = false;
                        this.clearSelectionsForOwner('subcategory', sibling.id);
                        if (Array.isArray(sibling.subcategories)) {
                            sibling.subcategories.forEach(ss => {
                                if (ss.open) ss.open = false;
                                this.clearSelectionsForOwner('subsubcategory', ss.id);
                            });
                        }
                    }
                });
                return;
            }

            // If focusing a SUB-SUBCATEGORY: close sibling sub-subs and purge their selections
            if (subsub != null && Array.isArray(subcat.subcategories)) {
                subcat.subcategories.forEach(siblingSS => {
                    if (siblingSS.id !== subsub.id) {
                        if (siblingSS.open) siblingSS.open = false;
                        this.clearSelectionsForOwner('subsubcategory', siblingSS.id);
                    }
                });
            }
        }
    },

        setCategoryPath({
            categoryId = null, categoryName = null,
            subcategoryId = null, subcategoryName = null,
            subsubcategoryId = null, subsubcategoryName = null
        }) {
        this.selected.categoryPath = {
            categoryId, categoryName,
            subcategoryId, subcategoryName,
            subsubcategoryId, subsubcategoryName
        };
    }
,

    collapseTree() {
        const closeBranch = (node) => {
            if (!node) return;
            node.open = false;
            // close any filter accordions at this node
            (node.filters || []).forEach(f => f.open = false);
            // recurse into children
            (node.subcategories || []).forEach(closeBranch);
        };

        this.categories.forEach(cat => {
            cat.open = false;
            (cat.filters || []).forEach(f => f.open = false);
            (cat.subcategories || []).forEach(closeBranch);
        });
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

    toggleFilter(filterName, optionValue, checked, meta = null) {
        // Maintain legacy selected.filters structure
        if (!this.selected.filters[filterName]) {
            this.selected.filters[filterName] = { name: filterName, values: [] };
        }
        const vals = this.selected.filters[filterName].values;

        if (checked) {
            if (!vals.includes(optionValue)) vals.push(optionValue);
        } else {
            this.selected.filters[filterName].values = vals.filter(v => v !== optionValue);
            if (this.selected.filters[filterName].values.length === 0) {
                delete this.selected.filters[filterName];
            }
        }

        // New: centralized, owner-aware selection map
        if (meta && meta.optionId != null) {
            const key = String(meta.optionId);
            if (checked) {
                this.selected.chosen[key] = {
                    filterName,
                    value: optionValue,
                    filterId: meta.filterId,
                    ownerType: meta.ownerType,  // 'category' | 'subcategory' | 'subsubcategory'
                    ownerId: meta.ownerId
                };
            } else {
                delete this.selected.chosen[key];
            }
        }
    }

    clearSelectionsForOwner(ownerType, ownerId) {
        const keys = Object.keys(this.selected.chosen);
        for (const k of keys) {
            const item = this.selected.chosen[k];
            if (item.ownerType === ownerType && String(item.ownerId) === String(ownerId)) {
                delete this.selected.chosen[k];
                // Also mirror removal in selected.filters
                const fn = item.filterName;
                if (this.selected.filters[fn]) {
                    this.selected.filters[fn].values = this.selected.filters[fn].values.filter(v => v !== item.value);
                    if (this.selected.filters[fn].values.length === 0) {
                        delete this.selected.filters[fn];
                    }
                }
            }
        }
        if (typeof this.onSelectionChange === 'function') {
            this.onSelectionChange();
        }
    }
    ;
}
const vals = this.selected.filters[filterName].values;

if (checked) {
    if (!vals.includes(optionValue)) vals.push(optionValue);
} else {
    this.selected.filters[filterName].values = vals.filter(v => v !== optionValue);
    if (this.selected.filters[filterName].values.length === 0) {
        delete this.selected.filters[filterName];
    }
}
},

isChecked(filterName, optionValue) {
    const group = this.selected.filters[filterName];
    return !!(group && group.values && group.values.includes(optionValue));
},

onSelectionChange() {
    const params = buildParamsFromSelections({
        categories: this.categories,
        selected: this.selected,
        globals:  this.globalFilters
    });
    window.history.replaceState({}, '', `?${params.toString()}`);
    runSearchWithOffset(0);
},

async submitFilters() {
    this.isLoadingFilters = true;

    // Build params from the structured state
    const params = buildParamsFromSelections({
        categories: this.categories,
        selected: this.selected,
        globals:   this.globalFilters
    });

    // Push to URL so pagination/share works, then search
    window.history.replaceState({}, '', `?${params.toString()}`);
    runSearchWithOffset(0);

    // Close drawer on mobile
    if (window.innerWidth < 768) {
        const outer = document.querySelector('[x-data]')?.__x?.$data;
        if (outer && typeof outer.showFilters !== 'undefined') {
            outer.showFilters = false;
        }
    }

    this.isLoadingFilters = false;
}


};
}

window.filterTree = filterTree;
