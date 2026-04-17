@extends('subcon.layout')

@section('content')
    <div class="space-y-6" x-data="subconBatchCreate()">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Create WH Send Subcon</h1>
                    <div class="mt-1 text-sm text-slate-600">Satu submit bisa membuat beberapa order subcon sekaligus untuk vendor yang sama.</div>
                </div>
                <a href="{{ route('subcon.traceability-index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form action="{{ route('subcon.store') }}" method="POST" class="space-y-6">
                @csrf

                @if (session('error'))
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 font-semibold">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                        <select name="vendor_id" required
                            x-model="vendor_id"
                            @change="onVendorChange()"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Vendor</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" @selected(old('vendor_id') == $v->id)>
                                    {{ !empty($v->vendor_code) ? $v->vendor_code . ' - ' : '' }}{{ $v->vendor_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nomor Kontrak <span class="text-red-500">*</span></label>
                        <select x-model="contract_no_selected"
                            @change="applyContractSelection()"
                            :disabled="!vendor_id"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" x-text="vendor_id ? 'Pilih nomor kontrak' : 'Pilih vendor dulu'"></option>
                            <template x-for="contract in availableContracts" :key="contract.id">
                                <option :value="contract.contract_no" x-text="contract.description ? `${contract.contract_no} - ${contract.description}` : contract.contract_no"></option>
                            </template>
                            <option value="__other__">LAINNYA...</option>
                        </select>
                        <input type="hidden" name="contract_no" x-model="contract_no" required>
                        <input type="text" x-show="showManualContract" x-cloak x-model="contract_no"
                            class="mt-2 w-full rounded-lg border-slate-300 text-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Masukkan nomor kontrak vendor/subcon" />
                        <p class="mt-1 text-xs text-slate-500" x-show="vendor_id && availableContracts.length === 0" x-cloak>
                            Belum ada master nomor kontrak untuk vendor ini. Silakan isi manual atau tambahkan di menu Contract Numbers.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Sent Date <span class="text-red-500">*</span></label>
                        <input type="date" name="sent_date" required value="{{ old('sent_date', now()->toDateString()) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Expected Return</label>
                        <input type="date" name="expected_return_date" value="{{ old('expected_return_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <div>
                        <div>
                            <div class="text-base font-bold text-slate-900">Item WH Send</div>
                            <div class="text-sm text-slate-500">Setiap baris akan menjadi 1 order subcon terpisah.</div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        <template x-for="(row, index) in rows" :key="row.key">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                                    <div class="flex items-center gap-4">
                                        <div class="text-sm font-black text-indigo-900 flex items-center gap-2">
                                            <div class="h-6 w-6 rounded bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs" x-text="(index + 1)"></div>
                                            <span x-text="row.process_type || 'Unknown Process'"></span>
                                        </div>
                                        <button type="button" @click="removeRow(index)" class="group flex items-center gap-1 text-rose-500 hover:text-rose-700 transition-colors">
                                            <div class="p-1.5 rounded-lg group-hover:bg-rose-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </div>
                                            <span class="text-[10px] font-bold uppercase">Remove</span>
                                        </button>
                                    </div>
                                    <div class="text-right" x-show="row.target_qty !== undefined">
                                        <div class="text-[10px] font-bold text-slate-500 uppercase">Sisa Qty Kontrak</div>
                                        <div class="text-sm font-black" :class="row.remaining_qty > 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="row.remaining_qty"></div>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                    <div class="xl:col-span-2 space-y-1">
                                        <label class="block text-[10px] font-bold uppercase text-slate-500">WIP Part (Hasil Vendor)</label>
                                        <div class="font-bold text-slate-900 border border-slate-200 bg-slate-50 px-3 py-2 rounded-lg text-sm" x-text="`${row.wip_part_no} - ${row.wip_part_name}`"></div>
                                        <input type="hidden" :name="`items[${index}][gci_part_id]`" :value="row.gci_part_id">
                                        <input type="hidden" :name="`items[${index}][bom_item_id]`" :value="row.bom_item_id">
                                        <input type="hidden" :name="`items[${index}][process_type]`" :value="row.process_type">
                                    </div>

                                    <div class="xl:col-span-2 space-y-1">
                                        <label class="block text-[10px] font-bold uppercase text-slate-500">RM Part (Barang Dikirim)</label>
                                        <div class="font-bold text-slate-900 border border-slate-200 bg-slate-50 px-3 py-2 rounded-lg text-sm" x-text="`${row.rm_part_no} - ${row.rm_part_name}`"></div>
                                        <input type="hidden" :name="`items[${index}][rm_gci_part_id]`" :value="row.rm_gci_part_id">
                                    </div>

                                    <div class="space-y-1">
                                        <label class="block text-[10px] font-bold uppercase text-slate-500">Qty Sent <span class="text-red-500">*</span></label>
                                        <input type="number"
                                            step="0.0001"
                                            min="0"
                                            :max="row.remaining_qty !== undefined ? row.remaining_qty : null"
                                            :name="`items[${index}][qty_sent]`"
                                            x-model="row.qty_sent"
                                            class="w-full rounded-lg border-indigo-300 bg-indigo-50/30 text-sm font-black focus:border-indigo-600 focus:ring-indigo-600 text-indigo-900"
                                            placeholder="Masukkan Qty"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="rows.length === 0" class="text-center py-12 rounded-2xl border-2 border-dashed border-slate-200 bg-white">
                            <div class="mb-2 text-slate-400">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <div class="text-sm font-bold text-slate-500">Pilih Nomor Kontrak</div>
                            <div class="text-xs text-slate-400 mt-1">Item part akan otomatis muncul sesuai dengan yang terdaftar di master kontrak.</div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 pt-6 border-t border-slate-100">
                    <div></div>
                    <div class="flex gap-3">
                        <a href="{{ route('subcon.traceability-index') }}"
                            class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit"
                            :disabled="rows.length === 0"
                            class="rounded-xl bg-indigo-600 px-8 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Create WH Send
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function subconBatchCreate() {
            const contracts = @json($contractsJson);

            return {
                contracts,
                vendor_id: @js((string) old('vendor_id', '')),
                contract_no: @js((string) old('contract_no', '')),
                contract_no_selected: '',
                availableContracts: [],
                showManualContract: false,
                rows: [],
                init() {
                    this.onVendorChange(false);
                    // For error responses with old rows
                    const oldRowsData = @json(old('items', []));
                    if (oldRowsData && oldRowsData.length > 0 && this.contract_no) {
                        // try to match with contract items
                        const matched = this.contracts.find(c => c.contract_no === this.contract_no);
                        if (matched && matched.items) {
                            this.rows = matched.items.map((item, idx) => {
                                const oldMatch = oldRowsData.find(oldItem => 
                                    oldItem.gci_part_id == item.gci_part_id && 
                                    oldItem.rm_gci_part_id == item.rm_gci_part_id &&
                                    oldItem.process_type === item.process_type
                                );
                                return {
                                    key: 'row-' + idx + '-' + Date.now(),
                                    gci_part_id: item.gci_part_id,
                                    rm_gci_part_id: item.rm_gci_part_id,
                                    bom_item_id: item.bom_item_id,
                                    process_type: item.process_type,
                                    wip_part_no: item.wip_part_no,
                                    wip_part_name: item.wip_part_name,
                                    rm_part_no: item.rm_part_no,
                                    rm_part_name: item.rm_part_name,
                                    target_qty: item.target_qty,
                                    remaining_qty: item.remaining_qty,
                                    qty_sent: oldMatch ? oldMatch.qty_sent : '',
                                };
                            });
                        }
                    }
                },
                onVendorChange(resetContract = true) {
                    this.availableContracts = this.contracts.filter(contract => contract.vendor_id === this.vendor_id);

                    if (!this.vendor_id) {
                        this.contract_no_selected = '';
                        this.showManualContract = false;
                        if (resetContract) this.contract_no = '';
                        this.rows = [];
                        return;
                    }

                    const matched = this.availableContracts.find(contract => contract.contract_no === this.contract_no);
                    if (matched) {
                        this.contract_no_selected = matched.contract_no;
                        this.showManualContract = false;
                        if (resetContract) {
                            this.applyContractSelection();
                        }
                        return;
                    }

                    this.showManualContract = this.availableContracts.length === 0 || this.contract_no_selected === '__other__';
                    this.contract_no_selected = this.contract_no ? '__other__' : '';
                    if (resetContract && !this.contract_no_selected) {
                        this.contract_no = '';
                        this.rows = [];
                    }
                },
                applyContractSelection() {
                    if (this.contract_no_selected === '__other__') {
                        this.showManualContract = true;
                        this.contract_no = '';
                        this.rows = [];
                        return;
                    }

                    this.showManualContract = false;
                    this.contract_no = this.contract_no_selected || '';
                    
                    if (this.contract_no) {
                        const matched = this.contracts.find(c => c.contract_no === this.contract_no);
                        if (matched && matched.items && matched.items.length > 0) {
                            this.rows = matched.items.map((item, idx) => ({
                                key: 'row-' + idx + '-' + Date.now(),
                                gci_part_id: item.gci_part_id,
                                rm_gci_part_id: item.rm_gci_part_id,
                                bom_item_id: item.bom_item_id,
                                process_type: item.process_type,
                                wip_part_no: item.wip_part_no,
                                wip_part_name: item.wip_part_name,
                                rm_part_no: item.rm_part_no,
                                rm_part_name: item.rm_part_name,
                                target_qty: item.target_qty,
                                remaining_qty: item.remaining_qty,
                                qty_sent: '',
                            }));
                        } else {
                            this.rows = [];
                            // Don't inject empty row if no items mapped. Must set mapping in master data first.
                        }
                    } else {
                        this.rows = [];
                    }
                },
                removeRow(index) {
                    if (confirm('Hapus baris ini dari pengiriman?')) {
                        this.rows.splice(index, 1);
                    }
                }
            }
        }
    </script>
@endsection
