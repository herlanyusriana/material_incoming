<x-app-layout>
    <x-slot name="header">
        Planning • Customer Part Mapping
    </x-slot>

    @push('head')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    @endpush

    <div class="py-3" x-data="planningCustomerParts()" x-init="init()">
	        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">Validation error</div>
                    <ul class="mt-1 list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

	            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-4 space-y-4">
	                <div class="flex flex-wrap items-end justify-between gap-3">
	                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div class="w-64">
                            <label class="text-xs font-semibold text-slate-600">Search</label>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search part no / name..." class="mt-1 w-full rounded-xl border-slate-200">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Customer</label>
                            <select name="customer_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected((string) $customerId === (string) $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
	                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
	                    </form>

	                    <div class="flex items-center gap-2">
	                        <a
	                            href="{{ route('planning.customer-parts.export', request()->query()) }}"
	                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-semibold"
	                        >
	                            Export
	                        </a>
	                        <button
	                            type="button"
	                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-semibold"
	                            @click="openImport()"
	                        >
	                            Import
	                        </button>
	                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
	                            Add Customer Part
	                        </button>
	                    </div>
	                </div>

                <div class="space-y-4">
                    @forelse ($customerParts as $cp)
                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs text-slate-500">{{ $cp->customer->code ?? '-' }} • {{ $cp->customer->name ?? '-' }}</div>
                                    <div class="flex items-center gap-3">
                                        <div class="text-lg font-semibold text-slate-900">{{ $cp->customer_part_no }}</div>
                                        <div class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                                            {{ $cp->components->count() }} Components
                                        </div>
                                    </div>
                                    <div class="text-sm text-slate-600">{{ $cp->customer_part_name ?? '-' }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $cp->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ strtoupper($cp->status) }}
                                    </span>
                                    <button type="button" class="p-1 px-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors" @click="openEdit(@js($cp))" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('planning.customer-parts.destroy', $cp) }}" method="POST" onsubmit="return confirm('Delete mapping?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="p-1 px-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" type="submit" title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="mt-2 flex items-center justify-between gap-4">
                                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Mapped Part GCI</div>
                                    <button 
                                        type="button" 
                                        class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-colors border border-indigo-200"
                                        @click="openComponentModal(@js($cp), @js($cp->components->map(fn($c) => ['id' => $c->id, 'part_id' => $c->part_id, 'part_no' => $c->part->part_no, 'part_name' => $c->part->part_name, 'usage_qty' => $c->usage_qty])))"
                                    >
                                        Manage Components
                                    </button>
                                </div>
                                <div class="mt-2 overflow-x-auto border border-slate-200 rounded-xl">
                                    <table class="min-w-full text-sm divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                                <th class="px-3 py-2 text-left font-semibold">Part No</th>
                                                <th class="px-3 py-2 text-left font-semibold">Part Name</th>
                                                <th class="px-3 py-2 text-right font-semibold">Consumption</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($cp->components as $comp)
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-3 py-2 font-semibold text-slate-700">{{ $comp->part->part_no ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600 text-xs">{{ $comp->part->part_name ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right font-mono text-xs text-indigo-600 font-bold whitespace-nowrap">{{ number_format((float) $comp->usage_qty, 3) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-3 py-4 text-center text-slate-500 italic">No components mapped</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-500">No customer part mapping</div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $customerParts->links() }}
                </div>
            </div>
        </div>

        {{-- Create/Edit Modal (Added) --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900" x-text="mode === 'create' ? 'Create Customer Part' : 'Edit Customer Part'"></h3>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="p-5 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="id" x-model="form.id">

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer</label>
                        <select name="customer_id" x-model="form.customer_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="">Select Customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer Part No</label>
                        <input type="text" name="customer_part_no" x-model="form.customer_part_no" class="mt-1 w-full rounded-xl border-slate-200" required>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Customer Part Name</label>
                        <input type="text" name="customer_part_name" x-model="form.customer_part_name" class="mt-1 w-full rounded-xl border-slate-200">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" x-model="form.status" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 font-semibold" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

	    {{-- Component Modal --}}
	    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="compModalOpen" x-cloak @keydown.escape.window="closeComponentModal()">
	        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-200 flex flex-col max-h-[90vh]">
	            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
	                <div>
                        <div class="text-xs text-slate-500 uppercase font-bold tracking-wider" x-text="compForm.customerCode"></div>
                        <div class="text-sm font-semibold text-slate-900" x-text="compForm.customerPartNo"></div>
                    </div>
	                <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeComponentModal()">✕</button>
	            </div>

	            <div class="flex-1 overflow-y-auto p-5 space-y-6">
                    {{-- Add New Component Form --}}
                    <div class="bg-indigo-50 rounded-2xl p-4 border border-indigo-100">
                        <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-widest mb-3">Add / Update Component</h4>
                        <form :action="compForm.action" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                            @csrf
                            <div class="md:col-span-2">
                                <label class="text-[10px] font-bold text-indigo-700 uppercase">Part GCI</label>
                                <select 
                                    id="part-select" 
                                    name="part_id" 
                                    class="mt-1 w-full rounded-xl border-indigo-200 bg-white" 
                                    required
                                >
                                    <option value="">Search part...</option>
                                    @foreach ($parts as $p)
                                        <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="text-[10px] font-bold text-indigo-700 uppercase">Consumption</label>
                                <input type="number" step="any" min="0" name="usage_qty" placeholder="0.000" class="mt-1 w-full rounded-xl border-indigo-200 bg-white text-sm" required>
                            </div>
                            <div class="md:col-span-1">
                                <button class="w-full px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all">
                                    ADD
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Existing Components List --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Existing Components</h4>
                        <div class="border border-slate-100 rounded-xl overflow-hidden shadow-sm">
                            <table class="min-w-full text-xs divide-y divide-slate-100">
                                <thead class="bg-slate-50">
                                    <tr class="text-slate-500 uppercase tracking-wider">
                                        <th class="px-4 py-2.5 text-left font-bold">GCI Part</th>
                                        <th class="px-4 py-2.5 text-right font-bold">Consumption</th>
                                        <th class="px-4 py-2.5 text-right font-bold">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-50">
                                    <template x-for="c in compForm.items" :key="c.id">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-800" x-text="c.part_no"></div>
                                                <div class="text-[10px] text-slate-500" x-text="c.part_name"></div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-indigo-600" x-text="Number(c.usage_qty).toFixed(3)"></td>
                                            <td class="px-4 py-3 text-right">
                                                <form :action="'{{ url('/planning/customer-parts/components') }}/' + c.id" method="POST" onsubmit="return confirm('Remove component?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="p-1 px-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 font-bold transition-colors">
                                                        ×
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="compForm.items.length === 0">
                                        <tr>
                                            <td colspan="3" class="px-4 py-8 text-center text-slate-400 italic">No components mapped yet.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100 flex justify-end">
                    <button type="button" class="px-6 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs" @click="closeComponentModal()">DONE</button>
                </div>
	        </div>
	    </div>

	    {{-- Import Modal --}}
	    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="importOpen" x-cloak @keydown.escape.window="closeImport()">
	        <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
	            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
	                <h3 class="text-lg font-bold text-slate-900">Import Customer Part Mapping</h3>
	                <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeImport()">✕</button>
	            </div>

	            <form action="{{ route('planning.customer-parts.import') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
	                @csrf
	                <div>
	                    <label class="text-sm font-semibold text-slate-700">Excel File</label>
	                    <input 
	                        type="file" 
	                        name="file" 
	                        accept=".xlsx,.xls,.csv" 
	                        class="mt-2 w-full rounded-xl border-slate-200" 
	                        required
	                    >
	                    <p class="mt-1 text-xs text-slate-500">
	                        Format: Customer Code, Customer Part No, Customer Part Name, Status, GCI Part No, GCI Part Name, Usage Qty
	                    </p>
	                </div>

	                <div class="flex items-center justify-end gap-2 pt-2">
	                    <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 font-semibold" @click="closeImport()">
	                        Cancel
	                    </button>
	                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
	                        Upload & Import
	                    </button>
	                </div>
	            </form>
	        </div>
	    </div>

	    <script>
	        function planningCustomerParts() {
	            return {
	                modalOpen: false,
	                importOpen: false,
                    compModalOpen: false,
                    ts: null,
	                mode: 'create',
	                formAction: @js(route('planning.customer-parts.store')),
	                form: { id: null, customer_id: '', customer_part_no: '', customer_part_name: '', status: 'active' },
                    compForm: {
                        action: '',
                        customerPartNo: '',
                        customerCode: '',
                        items: []
                    },
                    init() {
                        const prefillCustomerPartNo = @js(request('prefill_customer_part_no'));
                        const prefillCustomerId = @js($customerId);

                        if (prefillCustomerPartNo) {
                            this.openCreate();
                            if (prefillCustomerId) this.form.customer_id = String(prefillCustomerId);
                            this.form.customer_part_no = prefillCustomerPartNo;
                        }

                        // We initialize Tom Select next tick because it needs the element to be in DOM
                        this.$nextTick(() => {
                            this.ts = new TomSelect('#part-select', {
                                create: false,
                                placeholder: 'Search Part GCI...',
                                allowEmptyOption: true,
                                controlInput: null,
                                dropdownParent: 'body'
                            });
                        });
                    },
	                openCreate() {
	                    this.mode = 'create';
	                    this.formAction = @js(route('planning.customer-parts.store'));
	                    this.form = { id: null, customer_id: '', customer_part_no: '', customer_part_name: '', status: 'active' };
	                    this.modalOpen = true;
	                },
	                openImport() { this.importOpen = true; },
	                closeImport() { this.importOpen = false; },
	                openEdit(cp) {
	                    this.mode = 'edit';
	                    this.formAction = @js(url('/planning/customer-parts')) + '/' + cp.id;
                        this.form = {
                            id: cp.id,
                            customer_id: cp.customer_id,
                            customer_part_no: cp.customer_part_no,
                            customer_part_name: cp.customer_part_name,
                            status: cp.status,
                        };
                        this.modalOpen = true;
	                },
	                close() { this.modalOpen = false; },
                    openComponentModal(cp, items) {
                        this.compForm.action = @js(url('/planning/customer-parts')) + '/' + cp.id + '/components';
                        this.compForm.customerPartNo = cp.customer_part_no;
                        this.compForm.customerCode = cp.customer?.code ?? '-';
                        this.compForm.items = items;
                        this.compModalOpen = true;
                        
                        this.$nextTick(() => {
                            if (this.ts) {
                                this.ts.clear();
                                this.ts.focus();
                            }
                        });
                    },
                    closeComponentModal() { 
                        this.compModalOpen = false; 
                    }
	            }
	        }
	    </script>
    </div>
</x-app-layout>
