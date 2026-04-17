<x-app-layout>
    <x-slot name="header">
        Contract Numbers
    </x-slot>

    <div class="space-y-6" x-data="contractManager()">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Management Nomor Kontrak</h1>
                    <p class="mt-1 text-sm text-slate-500">Master nomor kontrak vendor agar flow subcon tidak perlu input manual terus.</p>
                </div>
                <button type="button" @click="openCreate = !openCreate" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Tambah Nomor Kontrak
                </button>
            </div>

            {{-- Create Form --}}
            <div x-show="openCreate" x-cloak class="border-b border-indigo-100 bg-indigo-50/30 px-6 py-6">
                <form method="POST" action="{{ route('contract-numbers.store') }}" class="space-y-6">
                    @csrf
                    <div class="grid gap-4 lg:grid-cols-4">
                        <div class="col-span-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor <span class="text-red-500">*</span></label>
                            <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                <option value="">Pilih vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak <span class="text-red-500">*</span></label>
                            <input type="text" name="contract_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        </div>
                        <div class="col-span-4">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi Kontrak</label>
                            <input type="text" name="description" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Contoh: Hardening & Plating Parts Q2">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From <span class="text-red-500">*</span></label>
                            <input type="date" name="effective_from" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Expired Date</label>
                            <input type="date" name="effective_to" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status <span class="text-red-500">*</span></label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Items/Parts</h3>
                                <p class="text-xs text-slate-500">Definisikan part apa saja yang masuk ke dalam master kontrak ini beserta target kuantitasnya.</p>
                            </div>
                            <button type="button" @click="addCreateRow()" class="rounded-lg bg-indigo-100 text-indigo-700 px-3 py-1.5 text-xs font-bold hover:bg-indigo-200">
                                + Tambah Part
                            </button>
                        </div>
                        <div class="space-y-4">
                            <template x-for="(row, index) in createRows" :key="row.id">
                                <div class="grid grid-cols-12 gap-3 items-end p-3 rounded-xl border border-slate-100 bg-slate-50">
                                    <div class="col-span-12 lg:col-span-7">
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Part Mapping (WIP - RM) <span class="text-red-500">*</span></label>
                                        <select class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                            x-model="row.selected_part_key" @change="onCreatePartChange(index)" required>
                                            <option value="">Pilih Part & Process...</option>
                                            <template x-for="opt in subconPartsOptions" :key="opt.key">
                                                <option :value="opt.key" x-text="`${opt.part_no} | ${opt.part_name} (${opt.process_name}) => RM: ${opt.rm_part_no}`"></option>
                                            </template>
                                        </select>
                                        <input type="hidden" :name="`items[${index}][gci_part_id]`" x-model="row.gci_part_id">
                                        <input type="hidden" :name="`items[${index}][rm_gci_part_id]`" x-model="row.rm_gci_part_id">
                                        <input type="hidden" :name="`items[${index}][process_type]`" x-model="row.process_type">
                                        <input type="hidden" :name="`items[${index}][bom_item_id]`" x-model="row.bom_item_id">
                                    </div>
                                    <div class="col-span-6 lg:col-span-4">
                                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Target Qty Kontrak <span class="text-red-500">*</span></label>
                                        <input type="number" step="0.0001" min="0" :name="`items[${index}][target_qty]`" x-model="row.target_qty" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="0" required>
                                    </div>
                                    <div class="col-span-6 lg:col-span-1 pb-1">
                                        <button type="button" x-show="createRows.length > 1" @click="createRows.splice(index, 1)" class="w-full rounded-lg border border-red-200 text-red-600 px-3 py-2 text-xs font-bold hover:bg-red-50">
                                            X
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes / Lampiran Info</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="openCreate = false" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-white">Cancel</button>
                        <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Save Contract</button>
                    </div>
                </form>
            </div>

            {{-- Filter --}}
            <div class="border-b border-slate-100 px-6 py-4">
                <form method="GET" class="grid gap-3 lg:grid-cols-4">
                    <input name="search" value="{{ $filters['search'] }}" class="rounded-xl border-slate-200 text-sm lg:col-span-2" placeholder="Cari nomor kontrak atau deskripsi">
                    <select name="vendor_id" class="rounded-xl border-slate-200 text-sm">
                        <option value="">Semua Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($filters['vendorId'] == $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="status" class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">Semua Status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                        </select>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Nomor Kontrak</th>
                            <th class="px-4 py-3 font-semibold">Vendor</th>
                            <th class="px-4 py-3 font-semibold">Item Parts</th>
                            <th class="px-4 py-3 font-semibold">Periode</th>
                            <th class="px-4 py-3 font-semibold text-center">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($contracts as $contract)
                            @php 
                                $jsItems = $contract->items->map(function($item) use ($subconPartsJson) {
                                    $opt = collect($subconPartsJson)->firstWhere('bom_item_id', (string)$item->bom_item_id);
                                    return [
                                        'id' => rand(1000, 999999),
                                        'selected_part_key' => $opt ? $opt['key'] : '',
                                        'gci_part_id' => $item->gci_part_id,
                                        'rm_gci_part_id' => $item->rm_gci_part_id,
                                        'process_type' => $item->process_type,
                                        'bom_item_id' => $item->bom_item_id,
                                        'target_qty' => (float)$item->target_qty,
                                    ];
                                })->toJson();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $contract->contract_no }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $contract->description ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 font-semibold">{{ $contract->vendor?->vendor_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-xs">
                                        @if($contract->items->count() > 0)
                                            <span class="inline-block px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold border border-indigo-200">{{ $contract->items->count() }} Items</span>
                                        @else
                                            <span class="text-slate-400 italic">No Items</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="font-semibold">{{ $contract->effective_from?->format('d M Y') ?: '-' }}</div>
                                    <div class="text-[10px] uppercase font-bold text-slate-500 mt-0.5">Exp: {{ $contract->effective_to?->format('d M Y') ?: 'OPEN' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $contract->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ strtoupper($contract->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100"
                                            @click="toggleEdit({{ $contract->id }}, {{ $jsItems }})">
                                            Tinjau / Edit
                                        </button>
                                        <form action="{{ route('contract-numbers.destroy', $contract) }}" method="POST" onsubmit="return confirm('Hapus nomor kontrak beserta itemnya secara permanen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Del</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            
                            {{-- Inline Edit Form for this Contract --}}
                            <tr x-show="openEditId === {{ $contract->id }}" x-cloak>
                                <td colspan="6" class="bg-indigo-50/20 px-0 py-0 border-b-2 border-indigo-200">
                                    <div class="p-6">
                                        <form action="{{ route('contract-numbers.update', $contract) }}" method="POST" class="space-y-6">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid gap-4 lg:grid-cols-4">
                                                <div class="col-span-2">
                                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</label>
                                                    <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                        @foreach($vendors as $vendor)
                                                            <option value="{{ $vendor->id }}" @selected($contract->vendor_id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak</label>
                                                    <input type="text" name="contract_no" value="{{ $contract->contract_no }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                </div>
                                                <div class="col-span-4">
                                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi</label>
                                                    <input type="text" name="description" value="{{ $contract->description }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From</label>
                                                    <input type="date" name="effective_from" value="{{ $contract->effective_from?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                </div>
                                                <div>
                                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Expired Date</label>
                                                    <input type="date" name="effective_to" value="{{ $contract->effective_to?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                                                    <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                        <option value="active" @selected($contract->status === 'active')>Active</option>
                                                        <option value="inactive" @selected($contract->status === 'inactive')>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-white p-4 mt-4 shadow-sm">
                                                <div class="flex items-center justify-between mb-4">
                                                    <div>
                                                        <h3 class="text-sm font-bold text-slate-900 border-b-2 border-indigo-500 inline-block pb-1">Master Items / Target Qty</h3>
                                                    </div>
                                                    <button type="button" @click="addEditRow()" class="rounded-lg bg-indigo-100 text-indigo-700 px-3 py-1.5 text-xs font-bold hover:bg-indigo-200">
                                                        + Tambah Part
                                                    </button>
                                                </div>
                                                
                                                <div class="space-y-3">
                                                    <template x-for="(row, index) in editRows" :key="row.id">
                                                        <div class="grid grid-cols-12 gap-3 items-end p-3 rounded-xl border border-slate-100 bg-slate-50">
                                                            <div class="col-span-12 lg:col-span-7">
                                                                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Part Mapping <span class="text-red-500">*</span></label>
                                                                <select class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                                    x-model="row.selected_part_key" @change="onEditPartChange(index)" required>
                                                                    <option value="">Pilih Part & Process...</option>
                                                                    <template x-for="opt in subconPartsOptions" :key="opt.key">
                                                                        <option :value="opt.key" x-text="`${opt.part_no} | ${opt.part_name} (${opt.process_name}) => RM: ${opt.rm_part_no}`"></option>
                                                                    </template>
                                                                </select>
                                                                <input type="hidden" :name="`items[${index}][gci_part_id]`" x-model="row.gci_part_id">
                                                                <input type="hidden" :name="`items[${index}][rm_gci_part_id]`" x-model="row.rm_gci_part_id">
                                                                <input type="hidden" :name="`items[${index}][process_type]`" x-model="row.process_type">
                                                                <input type="hidden" :name="`items[${index}][bom_item_id]`" x-model="row.bom_item_id">
                                                            </div>
                                                            <div class="col-span-6 lg:col-span-4">
                                                                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Target Qty Kontrak <span class="text-red-500">*</span></label>
                                                                <input type="number" step="0.0001" min="0" :name="`items[${index}][target_qty]`" x-model="row.target_qty" class="w-full rounded-lg border-slate-300 text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500" placeholder="0" required>
                                                            </div>
                                                            <div class="col-span-6 lg:col-span-1 pb-1">
                                                                <button type="button" @click="editRows.splice(index, 1)" class="w-full rounded-lg border border-red-200 text-red-600 px-3 py-2 text-xs font-bold hover:bg-red-50">
                                                                    X
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <div x-show="editRows.length === 0" class="text-center py-4 bg-slate-50 rounded-lg border border-slate-100 text-slate-500 text-sm italic">
                                                        Belum ada item di kontrak ini. Silakan klik + Tambah Part.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes / Lampiran Info</label>
                                                <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ $contract->notes }}</textarea>
                                            </div>
                                            <div class="flex justify-end gap-3 mt-6">
                                                <button type="button" @click="openEditId = null" class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                                                <button class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 focus:ring focus:ring-indigo-200 transition-colors">Update Contract</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="text-slate-400 font-semibold">Belum ada nomor kontrak.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-6 py-4">
                {{ $contracts->links() }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contractManager', () => ({
                openCreate: false,
                openEditId: null,
                subconPartsOptions: @json($subconPartsJson ?? []),
                createRows: [],
                editRows: [],

                init() {
                    // prefill with 1 row
                    this.addCreateRow();
                },

                addCreateRow() {
                    this.createRows.push({
                        id: Date.now() + Math.random(),
                        selected_part_key: '',
                        gci_part_id: '',
                        rm_gci_part_id: '',
                        process_type: '',
                        bom_item_id: '',
                        target_qty: ''
                    });
                },

                onCreatePartChange(index) {
                    const row = this.createRows[index];
                    const selected = this.subconPartsOptions.find(opt => opt.key === row.selected_part_key);
                    if (selected) {
                        row.gci_part_id = selected.id;
                        row.rm_gci_part_id = selected.rm_part_id;
                        row.process_type = selected.process_name;
                        row.bom_item_id = selected.bom_item_id;
                    } else {
                        row.gci_part_id = '';
                        row.rm_gci_part_id = '';
                        row.process_type = '';
                        row.bom_item_id = '';
                    }
                },

                toggleEdit(id, items) {
                    if (this.openEditId === id) {
                        this.openEditId = null;
                    } else {
                        this.openEditId = id;
                        // load items
                        this.editRows = JSON.parse(JSON.stringify(items));
                    }
                },

                addEditRow() {
                    this.editRows.push({
                        id: Date.now() + Math.random(),
                        selected_part_key: '',
                        gci_part_id: '',
                        rm_gci_part_id: '',
                        process_type: '',
                        bom_item_id: '',
                        target_qty: ''
                    });
                },

                onEditPartChange(index) {
                    const row = this.editRows[index];
                    const selected = this.subconPartsOptions.find(opt => opt.key === row.selected_part_key);
                    if (selected) {
                        row.gci_part_id = selected.id;
                        row.rm_gci_part_id = selected.rm_part_id;
                        row.process_type = selected.process_name;
                        row.bom_item_id = selected.bom_item_id;
                    } else {
                        row.gci_part_id = '';
                        row.rm_gci_part_id = '';
                        row.process_type = '';
                        row.bom_item_id = '';
                    }
                }
            }));
        });
    </script>
</x-app-layout>
