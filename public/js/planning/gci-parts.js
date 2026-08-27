// Part Master GCI — modal & form state (Alpine.js)
// Server-side data is injected via window.__PART_MASTER__ (see index.blade.php).
function partMaster() {
    const cfg = () => window.__PART_MASTER__ || {};
    const emptyForm = () => ({
        id: null,
        part_no: '',
        part_name: '',
        size: '',
        model: '',
        classification: 'FG',
        status: 'active',
        consumption_policy: 'backflush_return',
        customer_ids: [],
        destination_fg_ids: [],
        vendor_ids: [],
        substitutes_for: [],
        as_substitute: [],
    });

    return {
        open: false,
        importOpen: false,
        mode: 'create',
        formAction: '',
        form: emptyForm(),

        subsOpen: false,
        subEditId: null,
        subFormAction: '',
        subForm: { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' },

        init() {
            // Restore form after duplicate-part warning from the server
            const warning = cfg().duplicateWarning;
            if (!warning) return;

            this.mode = 'create';
            this.form = {
                ...emptyForm(),
                part_no: warning.part_no || '',
                part_name: warning.part_name || '',
                size: warning.size || '',
                model: warning.model || '',
                classification: warning.classification || 'FG',
                status: warning.status || 'active',
                consumption_policy: warning.consumption_policy
                    || (warning.is_backflush === '0' ? 'direct_issue' : 'backflush_return'),
                customer_ids: warning.customer_ids || [],
                destination_fg_ids: warning.destination_fg_ids || [],
                vendor_ids: warning.vendor_ids || [],
            };
            this.formAction = cfg().routes.store;
            this.open = true;

            setTimeout(() => {
                if (confirm("Part number '" + this.form.part_no + "' sudah ada. Lanjutkan buat duplikat?")) {
                    this.$refs.confirmDuplicate.value = '1';
                    this.$refs.form.submit();
                }
            }, 300);
        },

        openCreate() {
            this.mode = 'create';
            this.formAction = cfg().routes.store;
            this.form = { ...emptyForm(), classification: cfg().defaultClassification || 'FG' };
            this.resetSubs();
            this.open = true;
        },

        openEdit(p) {
            const maps = cfg().maps;
            this.mode = 'edit';
            this.formAction = cfg().routes.parts + '/' + p.id;
            this.form = {
                ...emptyForm(),
                id: p.id,
                part_no: p.part_no || '',
                part_name: p.part_name || '',
                size: p.size || '',
                model: p.model || '',
                classification: p.classification || 'FG',
                status: p.status || 'active',
                consumption_policy: p.consumption_policy
                    || ((p.is_backflush !== false && p.is_backflush !== 0) ? 'backflush_return' : 'direct_issue'),
                customer_ids: (p.customers || []).map((c) => Number(c.id)),
                destination_fg_ids: (maps.rmFg[p.id] || []).map(Number),
                vendor_ids: (maps.partVendor[p.id] || []).map(Number),
                substitutes_for: maps.substitutesFor[p.id] || [],
                as_substitute: maps.asSubstitute[p.id] || [],
            };
            this.resetSubs();
            this.open = true;
        },

        onClassificationChange() {
            if (this.form.classification === 'RM') {
                this.form.customer_ids = [];
                this.form.model = '';
            } else {
                this.form.vendor_ids = [];
                this.form.destination_fg_ids = [];
            }
        },

        close() {
            this.open = false;
            this.resetSubs();
        },

        resetSubs() {
            this.subsOpen = false;
            this.cancelSubEdit();
        },

        // --- Substitutes ---
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
        },

        cancelSubEdit() {
            this.subEditId = null;
            if (this.form.id) {
                this.subFormAction = cfg().routes.parts + '/' + this.form.id + '/substitutes';
            }
            this.subForm = { fg_part_id: '', substitute_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' };
        },

        deleteSub(s) {
            if (!confirm('Hapus substitute ' + s.substitute_part_no + '?')) return;
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = cfg().routes.substitutes + '/' + s.id;
            f.innerHTML = document.querySelector('meta[name=csrf-token]').outerHTML
                .replace('<meta', '<input type="hidden"').replace('name="csrf-token"', 'name="_token"');
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            f.appendChild(method);
            document.body.appendChild(f);
            f.submit();
        },
    };
}
