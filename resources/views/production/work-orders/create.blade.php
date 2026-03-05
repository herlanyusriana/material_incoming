<x-app-layout>
    <x-slot name="header">Create Work Order (M01)</x-slot>

    <div class="mx-auto max-w-4xl space-y-4" x-data="woCreate()" x-init="loadCandidates()">
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('production.work-orders.generate') }}" class="rounded-xl border bg-white p-5 space-y-5">
            @csrf

            <div>
                <p class="text-xs font-semibold text-slate-500">STEP 1</p>
                <label class="mt-1 block text-sm font-semibold text-slate-700">Source Type</label>
                <select name="source_type" x-model="sourceType" @change="loadCandidates" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    <option value="manual">Manual</option>
                    <option value="mrp">MRP</option>
                    <option value="outgoing_daily">Daily Planning Outgoing</option>
                </select>
            </div>

            <div x-show="sourceType !== 'manual'">
                <p class="text-xs font-semibold text-slate-500">STEP 2</p>
                <label class="mt-1 block text-sm font-semibold text-slate-700">Source Reference</label>
                <select name="source_ref_id" x-model="sourceRefId" @change="pickCandidate" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    <option value="">Pilih source reference...</option>
                    <template x-for="item in candidates" :key="item.id">
                        <option :value="item.id" x-text="item.label"></option>
                    </template>
                </select>
            </div>

            <div x-show="sourceType === 'manual'">
                <p class="text-xs font-semibold text-slate-500">STEP 2</p>
                <label class="mt-1 block text-sm font-semibold text-slate-700">FG Part</label>
                <select x-model="fgPartId" @change="syncManualFgLabel" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    <option value="">Pilih FG part...</option>
                    <template x-for="p in fgParts" :key="p.id">
                        <option :value="p.id" x-text="p.label"></option>
                    </template>
                </select>
            </div>

            <div>
                <p class="text-xs font-semibold text-slate-500">STEP 3</p>
                <p class="text-sm font-semibold text-slate-700">Review FG + Qty + Date</p>
                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500">FG Part</label>
                        <input type="text" x-model="fgLabel" :readonly="sourceType !== 'manual'" placeholder="FG part"
                            class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <input type="hidden" name="fg_part_id" :value="fgPartId">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500">Qty Plan</label>
                        <input type="number" step="0.0001" min="0.0001" name="qty_plan" x-model="qtyPlan"
                            :readonly="sourceType !== 'manual'"
                            class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500">Plan Date</label>
                        <input type="date" name="plan_date" x-model="planDate" :readonly="sourceType !== 'manual'"
                            class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Priority</label>
                    <select name="priority" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        @foreach ([1,2,3,4,5] as $n)
                            <option value="{{ $n }}" @selected($n===3)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500">Remarks</label>
                    <input type="text" name="remarks" class="mt-1 w-full rounded-lg border-slate-200 text-sm" placeholder="Optional note">
                </div>
            </div>

            <div class="border-t pt-4">
                <p class="text-xs text-slate-500">STEP 4</p>
                <button type="submit" class="mt-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Generate Work Order
                </button>
                <a href="{{ route('production.work-orders.index') }}"
                    class="ml-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function woCreate() {
                return {
                    sourceType: 'manual',
                    sourceRefId: '',
                    candidates: [],
                    fgParts: [],
                    fgPartId: '',
                    fgLabel: '',
                    qtyPlan: '',
                    planDate: '',

                    async loadCandidates() {
                        this.sourceRefId = '';
                        this.candidates = [];
                        this.fgParts = [];
                        this.fgPartId = '';
                        this.fgLabel = '';
                        this.qtyPlan = '';
                        this.planDate = '';

                        const url = `{{ route('production.work-orders.create-data') }}?source_type=${encodeURIComponent(this.sourceType)}`;
                        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.candidates = data.candidates || [];
                        this.fgParts = data.fg_parts || [];
                    },

                    pickCandidate() {
                        const id = String(this.sourceRefId || '');
                        const row = this.candidates.find(x => String(x.id) === id);
                        if (!row) {
                            this.fgPartId = '';
                            this.fgLabel = '';
                            this.qtyPlan = '';
                            this.planDate = '';
                            return;
                        }
                        this.fgPartId = String(row.fg_part_id || '');
                        this.fgLabel = `${row.fg_part_no || ''} - ${row.fg_part_name || ''}`.trim();
                        this.qtyPlan = row.qty_plan || '';
                        this.planDate = row.plan_date || '';
                    },

                    syncManualFgLabel() {
                        const id = String(this.fgPartId || '');
                        const row = this.fgParts.find(x => String(x.id) === id);
                        this.fgLabel = row ? row.label : '';
                    },
                }
            }

        </script>
    @endpush
</x-app-layout>
