<x-app-layout>
    <x-slot name="header">
        Edit Contract Number
    </x-slot>

    <div class="space-y-6" x-data="contractEdit()">
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
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="{{ route('contract-numbers.show', $contract) }}" class="text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-slate-700">
                        Back to Detail
                    </a>
                    <h1 class="mt-2 text-2xl font-black text-slate-900">Edit {{ $contract->contract_no }}</h1>
                    <p class="text-sm text-slate-500">Edit header kontrak dan daftar item kontrak di halaman terpisah.</p>
                </div>
            </div>

            <form action="{{ route('contract-numbers.update', $contract) }}" method="POST" class="space-y-6 p-6">
                @csrf
                @method('PUT')

                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</label>
                        <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected($contract->vendor_id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak</label>
                        <input type="text" name="contract_no" value="{{ old('contract_no', $contract->contract_no) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>
                    <div class="lg:col-span-4">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi</label>
                        <input type="text" name="description" value="{{ old('description', $contract->description) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From</label>
                        <input type="date" name="effective_from" value="{{ old('effective_from', $contract->effective_from?->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Expired</label>
                        <input type="date" name="effective_to" value="{{ old('effective_to', $contract->effective_to?->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            <option value="active" @selected($contract->status === 'active')>Active</option>
                            <option value="inactive" @selected($contract->status === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Items Kontrak</h2>
                            <p class="text-xs text-slate-500">Target dan alarm per WIP/RM.</p>
                        </div>
                        <button type="button" @click="addRow()" class="rounded-lg bg-indigo-100 px-3 py-1.5 text-xs font-black text-indigo-700 hover:bg-indigo-200">
                            + Tambah Part
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <div class="grid grid-cols-12 gap-3 rounded-xl border border-slate-200 bg-white p-3">
                                <div class="col-span-12 lg:col-span-7">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500">Part Mapping</label>
                                    <select class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-model="row.selected_part_key" @change="onPartChange(index)" required>
                                        <option value="">Pilih WIP - RM...</option>
                                        <template x-for="opt in subconPartsOptions" :key="opt.key">
                                            <option :value="opt.key" x-text="`${opt.part_no} | ${opt.part_name} (${opt.process_name}) => RM: ${opt.rm_part_no} / ${opt.uom || 'PCS'}`"></option>
                                        </template>
                                    </select>
                                    <input type="hidden" :name="`items[${index}][gci_part_id]`" x-model="row.gci_part_id">
                                    <input type="hidden" :name="`items[${index}][rm_gci_part_id]`" x-model="row.rm_gci_part_id">
                                    <input type="hidden" :name="`items[${index}][process_type]`" x-model="row.process_type">
                                    <input type="hidden" :name="`items[${index}][bom_item_id]`" x-model="row.bom_item_id">
                                </div>
                                <div class="col-span-6 lg:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500">
                                        Target
                                        <span class="text-indigo-600" x-text="row.uom ? `(${row.uom})` : ''"></span>
                                    </label>
                                    <input type="number" step="0.0001" min="0" :name="`items[${index}][target_qty]`" x-model="row.target_qty" class="mt-1 w-full rounded-lg border-slate-300 text-sm font-bold" required>
                                </div>
                                <div class="col-span-6 lg:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase text-slate-500">Alarm</label>
                                    <input type="number" step="0.0001" min="0" :name="`items[${index}][warning_limit_qty]`" x-model="row.warning_limit_qty" class="mt-1 w-full rounded-lg border-slate-300 text-sm font-bold">
                                </div>
                                <div class="col-span-12 flex items-end lg:col-span-1">
                                    <button type="button" @click="rows.splice(index, 1)" class="w-full rounded-lg border border-rose-200 px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-50">
                                        X
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('notes', $contract->notes) }}</textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('contract-numbers.show', $contract) }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                    <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                        Update Contract
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contractEdit', () => ({
                subconPartsOptions: @json($subconPartsJson ?? []),
                rows: @json($editItemsJson ?? []),
                addRow() {
                    this.rows.push({
                        id: Date.now() + Math.random(),
                        selected_part_key: '',
                        gci_part_id: '',
                        rm_gci_part_id: '',
                        process_type: '',
                        bom_item_id: '',
                        uom: '',
                        target_qty: '',
                        warning_limit_qty: '',
                    });
                },
                onPartChange(index) {
                    const row = this.rows[index];
                    const selected = this.subconPartsOptions.find(opt => opt.key === row.selected_part_key);
                    if (selected) {
                        row.gci_part_id = selected.id;
                        row.rm_gci_part_id = selected.rm_part_id;
                        row.process_type = selected.process_name;
                        row.bom_item_id = selected.bom_item_id;
                        row.uom = selected.uom || 'PCS';
                    } else {
                        row.gci_part_id = '';
                        row.rm_gci_part_id = '';
                        row.process_type = '';
                        row.bom_item_id = '';
                        row.uom = '';
                    }
                },
            }));
        });
    </script>
</x-app-layout>
