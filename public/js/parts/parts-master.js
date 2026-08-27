// Parts Master — Alpine component.
// Server data injected via window.__PARTS_MASTER__ (see parts/index.blade.php).
function partsMaster() {
    const cfg = () => window.__PARTS_MASTER__ || {};
    const emptyPartForm = (classification) => ({
        id: null,
        part_no: '',
        part_name: '',
        size: '',
        model: '',
        classification: classification || 'RM',
        status: 'active',
        consumption_policy: 'backflush_return',
        customer_ids: [],
        vendor_ids: [],
        subcount_enabled: false,
        subcount_fg_part_id: '',
        subcount_rm_part_id: '',
        subcount_uom: 'PCE',
        subcount_process_type: 'PG',
        substitutes_for: [],
        as_substitute: [],
    });
    const emptySubForm = () => ({ fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' });
    const emptyVpForm = () => ({
        vendor_id: '', vendor_part_no: '', vendor_part_name: '', vendor_part_name_selected: '',
        register_no: '', uom: '', hs_code: '', quality_inspection: '', status: 'active',
    });

    return {
        expanded: {},
        selectedPartIds: [],
        importOpen: false,
        subImportOpen: false,

        // Part modal
        partModal: false,
        partMode: 'create',
        partAction: '',
        subsOpen: false,
        partForm: emptyPartForm(),
        subEditId: null,
        subFormAction: '',
        subForm: emptySubForm(),
        subFgOptions: [],

        // Vendor part modal
        vpModal: false,
        vpMode: 'create',
        vpAction: '',
        vpNameLoading: false,
        vpNameOptions: [],
        vpForm: emptyVpForm(),

        // Substitute modal (SUB tab)
        subListEditOpen: false,
        subListEditAction: '',
        subListForm: { id: '', fg_part_no: '', component_part_no: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' },

        // --- Row selection ---
        toggle(id) {
            this.expanded[id] = !this.expanded[id];
        },

        allVisibleSelected() {
            const selected = this.selectedPartIds.map(String);
            return cfg().visiblePartIds.length > 0
                && cfg().visiblePartIds.every((id) => selected.includes(String(id)));
        },

        toggleSelectAll(checked) {
            const selected = this.selectedPartIds.map(String);
            const visible = cfg().visiblePartIds.map(String);
            this.selectedPartIds = checked
                ? Array.from(new Set([...selected, ...visible]))
                : selected.filter((id) => !visible.includes(id));
        },

        // --- Part modal ---
        openCreatePart() {
            this.partMode = 'create';
            this.partAction = cfg().routes.store;
            this.partForm = emptyPartForm(cfg().activeTab === 'SUB' ? 'RM' : cfg().activeTab);
            this.resetSubs();
            this.partModal = true;
        },

        openCreateSubcountPart() {
            this.openCreatePart();
            this.partForm.classification = 'WIP';
            this.partForm.subcount_enabled = true;
        },

        openEditPart(p) {
            const maps = cfg().maps;
            this.partMode = 'edit';
            this.partAction = cfg().routes.parts + '/' + p.id;
            const fgMap = {};
            (maps.fgPartsWithBom || []).forEach((fg) => { fgMap[String(fg.id)] = fg; });
            this.subFgOptions = (maps.rmFg[p.id] || []).map((id) => fgMap[String(id)]).filter(Boolean);
            this.partForm = {
                ...emptyPartForm(p.classification),
                id: p.id,
                part_no: p.part_no,
                part_name: p.part_name || '',
                size: p.size || '',
                model: p.model || '',
                status: p.status,
                consumption_policy: p.consumption_policy
                    || ((p.is_backflush !== false && p.is_backflush !== 0) ? 'backflush_return' : 'direct_issue'),
                subcount_enabled: !!p.subcount_enabled,
                subcount_fg_part_id: p.subcount_fg_part_id ? String(p.subcount_fg_part_id) : '',
                subcount_rm_part_id: p.subcount_rm_part_id ? String(p.subcount_rm_part_id) : '',
                subcount_uom: p.subcount_uom || 'PCE',
                subcount_process_type: p.subcount_process_type || 'PG',
                vendor_ids: maps.partVendor[p.id] || [],
                substitutes_for: maps.substitutesFor[p.id] || [],
                as_substitute: maps.asSubstitute[p.id] || [],
            };
            this.subFormAction = cfg().routes.parts + '/' + p.id + '/substitutes';
            this.cancelSubEdit();
            this.partModal = true;
        },

        // Subcount dropdowns: only show parts related to the part_no being edited
        normalizedPartCode(value) {
            return String(value || '').toUpperCase().trim()
                .replace(/\s+/g, '')
                .replace(/-(PLATING|HARDENING|HARDEN|SUBCON|PG)$/i, '')
                .replace(/-WIP\d*$/i, '');
        },

        subcountOptionMatches(option, selectedId = '') {
            if (selectedId && String(option.id) === String(selectedId)) return true;
            const base = this.normalizedPartCode(this.partForm.part_no);
            if (base.length < 4) return false;
            const candidate = this.normalizedPartCode(option.part_no);
            return candidate.includes(base) || base.includes(candidate);
        },

        get filteredSubcountParentOptions() {
            return cfg().options.subcountParents
                .filter((o) => this.subcountOptionMatches(o, this.partForm.subcount_fg_part_id))
                .slice(0, 50);
        },

        get filteredSubcountSourceOptions() {
            return cfg().options.subcountSources
                .filter((o) => this.subcountOptionMatches(o, this.partForm.subcount_rm_part_id))
                .slice(0, 50);
        },

        vendorMatches(name, search) {
            const norm = (v) => String(v || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
            const needle = norm(search);
            if (!needle) return true;
            const haystack = norm(name);
            return needle.split(' ').every((term) => haystack.includes(term));
        },

        // --- Substitutes (inside part modal) ---
        editSub(s) {
            this.subEditId = s.id;
            this.subFormAction = cfg().routes.substitutes + '/' + s.id;
            this.subForm = {
                fg_part_id: String(s.fg_part_id || ''),
                substitute_part_id: String(s.substitute_part_id || ''),
                ratio: s.ratio || 1,
                priority: s.priority || 1,
                status: s.status || 'active',
                notes: s.notes || '',
            };
            this.subsOpen = true;
        },

        cancelSubEdit() {
            this.subEditId = null;
            if (this.partForm.id) {
                this.subFormAction = cfg().routes.parts + '/' + this.partForm.id + '/substitutes';
            }
            this.subForm = emptySubForm();
        },

        deleteSub(s) {
            if (!confirm('Hapus substitute ' + s.substitute_part_no + '?')) return;
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = cfg().routes.substitutes + '/' + s.id;
            f.innerHTML = document.querySelector('meta[name=csrf-token]').outerHTML
                .replace('<meta', '<input type="hidden"').replace('name="csrf-token"', 'name="_token"');
            const m = document.createElement('input');
            m.type = 'hidden';
            m.name = '_method';
            m.value = 'DELETE';
            f.appendChild(m);
            document.body.appendChild(f);
            f.submit();
        },

        resetSubs() {
            this.subsOpen = false;
            this.cancelSubEdit();
        },

        // --- Substitute modal (SUB tab) ---
        openEditSubFromSubTab(s) {
            this.subListEditAction = cfg().routes.substitutes + '/' + s.id;
            this.subListForm = {
                id: String(s.id || ''),
                fg_part_no: s.fg_part_no || '',
                component_part_no: s.component_part_no || '',
                substitute_part_id: String(s.substitute_part_id || ''),
                ratio: s.ratio || 1,
                priority: s.priority || 1,
                status: s.status || 'active',
                notes: s.notes || '',
            };
            this.subListEditOpen = true;
        },

        // --- Vendor part modal ---
        openCreateVendorPart(partId) {
            this.vpMode = 'create';
            this.vpAction = cfg().routes.parts + '/' + partId + '/vendor-parts';
            this.vpNameOptions = [];
            this.vpForm = emptyVpForm();
            this.vpModal = true;
        },

        openEditVendorPart(vl) {
            this.vpMode = 'edit';
            this.vpAction = cfg().routes.vendorParts + '/' + vl.id;
            this.vpNameOptions = [];
            this.vpForm = {
                ...emptyVpForm(),
                vendor_id: vl.vendor_id,
                vendor_part_no: vl.vendor_part_no || '',
                vendor_part_name: vl.vendor_part_name || '',
                register_no: vl.register_no || '',
                uom: vl.uom || '',
                hs_code: vl.hs_code || '',
                quality_inspection: vl.quality_inspection ? 'YES' : '',
                status: vl.status || 'active',
            };
            this.loadVendorPartNames(vl.vendor_id, vl.vendor_part_name || '');
            this.vpModal = true;
        },

        async loadVendorPartNames(vendorId, preferredName = '') {
            this.vpNameOptions = [];
            this.vpNameLoading = false;
            if (!vendorId) {
                this.vpForm.vendor_part_name_selected = preferredName ? '__other__' : '';
                return;
            }
            this.vpNameLoading = true;
            try {
                const res = await fetch(cfg().routes.vendors + '/' + vendorId + '/vendor-part-names', {
                    headers: { Accept: 'application/json' },
                });
                const payload = await res.json();
                this.vpNameOptions = Array.isArray(payload.names) ? payload.names : [];
                if (preferredName && this.vpNameOptions.includes(preferredName)) {
                    this.vpForm.vendor_part_name_selected = preferredName;
                    this.vpForm.vendor_part_name = preferredName;
                } else if (preferredName) {
                    this.vpForm.vendor_part_name_selected = '__other__';
                    this.vpForm.vendor_part_name = preferredName;
                } else {
                    this.vpForm.vendor_part_name_selected = '';
                    this.vpForm.vendor_part_name = '';
                }
            } catch (e) {
                this.vpNameOptions = [];
                this.vpForm.vendor_part_name_selected = preferredName ? '__other__' : '';
                this.vpForm.vendor_part_name = preferredName || '';
            } finally {
                this.vpNameLoading = false;
            }
        },

        applyVendorPartNameSelection() {
            if (this.vpForm.vendor_part_name_selected === '__other__') {
                if (!this.vpForm.vendor_part_name || this.vpNameOptions.includes(this.vpForm.vendor_part_name)) {
                    this.vpForm.vendor_part_name = '';
                }
                return;
            }
            this.vpForm.vendor_part_name = this.vpForm.vendor_part_name_selected || '';
        },
    };
}
