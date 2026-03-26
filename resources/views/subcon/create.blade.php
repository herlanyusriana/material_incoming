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
                        <input type="text" name="contract_no" required value="{{ old('contract_no') }}"
                            class="w-full rounded-lg border-slate-300 text-sm uppercase focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Masukkan nomor kontrak vendor/subcon" />
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
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-base font-bold text-slate-900">Item WH Send</div>
                            <div class="text-sm text-slate-500">Setiap baris akan menjadi 1 order subcon terpisah.</div>
                        </div>
                        <button type="button"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700"
                            @click="addRow()">
                            + Add Item
                        </button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <template x-for="(row, index) in rows" :key="row.key">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-bold text-slate-900" x-text="'Item #' + (index + 1)"></div>
                                    <button type="button"
                                        class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50"
                                        x-show="rows.length > 1"
                                        @click="removeRow(index)">
                                        Remove
                                    </button>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                                    <div class="xl:col-span-2">
                                        <label class="block text-sm font-bold text-slate-700 mb-1">WIP Part (Hasil Vendor) <span class="text-red-500">*</span></label>
                                        <select class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            :name="`items[${index}][gci_part_id]`"
                                            x-model="row.gci_part_id"
                                            @change="onWipChange(index)"
                                            required>
                                            <option value="">Select WIP Part</option>
                                            <template x-for="part in subconParts" :key="part.key">
                                                <option :value="part.id" x-text="`${part.part_no} - ${part.part_name}`"></option>
                                            </template>
                                        </select>
                                        <input type="hidden" :name="`items[${index}][bom_item_id]`" x-model="row.bom_item_id">
                                    </div>

                                    <div class="xl:col-span-2">
                                        <label class="block text-sm font-bold text-slate-700 mb-1">RM Part (Barang Dikirim) <span class="text-red-500">*</span></label>
                                        <select class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            :name="`items[${index}][rm_gci_part_id]`"
                                            x-model="row.rm_gci_part_id"
                                            @change="onRmChange(index)"
                                            required>
                                            <option value="">Select RM Part</option>
                                            <template x-for="part in rmParts" :key="part.key">
                                                <option :value="part.rm_part_id" x-text="`${part.rm_part_no || '-'} - ${part.rm_part_name || '-'}`"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Qty Sent <span class="text-red-500">*</span></label>
                                        <input type="number"
                                            step="0.0001"
                                            min="0.0001"
                                            :name="`items[${index}][qty_sent]`"
                                            x-model="row.qty_sent"
                                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Process Type <span class="text-red-500">*</span></label>
                                        <input type="text"
                                            :name="`items[${index}][process_type]`"
                                            x-model="row.process_type"
                                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="e.g. plating, hardening"
                                            required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">WH Send Location <span class="text-red-500">*</span></label>
                                        <input type="text"
                                            :name="`items[${index}][send_location_code]`"
                                            x-model="row.send_location_code"
                                            class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Auto dari default location RM part"
                                            readonly
                                            required>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                        Create WH Send
                    </button>
                    <a href="{{ route('subcon.traceability-index') }}"
                        class="rounded-lg bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function subconBatchCreate() {
            const subconParts = @json(collect($subconParts)->map(function ($part, $idx) {
                return [
                    'key' => 'wip-' . $idx,
                    'id' => (string) $part['id'],
                    'part_no' => $part['part_no'] ?? '',
                    'part_name' => $part['part_name'] ?? '',
                    'rm_part_id' => isset($part['rm_part_id']) ? (string) $part['rm_part_id'] : '',
                    'process_name' => $part['process_name'] ?? '',
                    'bom_item_id' => isset($part['bom_item_id']) ? (string) $part['bom_item_id'] : '',
                ];
            })->values());

            const rmParts = @json(collect($rmParts)->map(function ($part, $idx) {
                return [
                    'key' => 'rm-' . $idx,
                    'rm_part_id' => isset($part['rm_part_id']) ? (string) $part['rm_part_id'] : '',
                    'rm_part_no' => $part['rm_part_no'] ?? '',
                    'rm_part_name' => $part['rm_part_name'] ?? '',
                    'default_location' => $part['default_location'] ?? '',
                ];
            })->values());

            const oldRows = @json(old('items', [
                [
                    'gci_part_id' => '',
                    'rm_gci_part_id' => '',
                    'bom_item_id' => '',
                    'process_type' => '',
                    'qty_sent' => '',
                    'send_location_code' => '',
                ],
            ]));

            return {
                subconParts,
                rmParts,
                rows: [],
                init() {
                    this.rows = oldRows.map((row, idx) => ({
                        key: 'row-' + idx + '-' + Date.now(),
                        gci_part_id: row.gci_part_id ? String(row.gci_part_id) : '',
                        rm_gci_part_id: row.rm_gci_part_id ? String(row.rm_gci_part_id) : '',
                        bom_item_id: row.bom_item_id ? String(row.bom_item_id) : '',
                        process_type: row.process_type || '',
                        qty_sent: row.qty_sent || '',
                        send_location_code: row.send_location_code || '',
                    }));

                    if (this.rows.length === 0) {
                        this.addRow();
                    }

                    this.rows.forEach((_, index) => {
                        if (this.rows[index].gci_part_id) {
                            this.onWipChange(index, false);
                        }
                        if (this.rows[index].rm_gci_part_id) {
                            this.onRmChange(index, false);
                        }
                    });
                },
                addRow() {
                    this.rows.push({
                        key: 'row-' + this.rows.length + '-' + Date.now(),
                        gci_part_id: '',
                        rm_gci_part_id: '',
                        bom_item_id: '',
                        process_type: '',
                        qty_sent: '',
                        send_location_code: '',
                    });
                },
                removeRow(index) {
                    if (this.rows.length <= 1) {
                        return;
                    }
                    this.rows.splice(index, 1);
                },
                onWipChange(index, overwrite = true) {
                    const selected = this.subconParts.find(part => part.id === this.rows[index].gci_part_id);
                    if (!selected) {
                        return;
                    }
                    if (overwrite || !this.rows[index].process_type) {
                        this.rows[index].process_type = selected.process_name || '';
                    }
                    this.rows[index].bom_item_id = selected.bom_item_id || '';
                    if (selected.rm_part_id) {
                        this.rows[index].rm_gci_part_id = selected.rm_part_id;
                        this.onRmChange(index, overwrite);
                    }
                },
                onRmChange(index, overwrite = true) {
                    const selected = this.rmParts.find(part => part.rm_part_id === this.rows[index].rm_gci_part_id);
                    if (!selected) {
                        return;
                    }
                    if (overwrite || !this.rows[index].send_location_code) {
                        this.rows[index].send_location_code = selected.default_location || '';
                    }
                },
            }
        }
    </script>
@endsection
