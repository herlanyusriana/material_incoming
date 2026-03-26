@extends('subcon.layout')

@section('content')
    <div class="space-y-6" x-data="subconCreate()">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">New Subcon Order</h1>
                    <div class="mt-1 text-sm text-slate-600">WH send RM part to vendor and receive processed WIP part back to WH.</div>
                </div>
                <a href="{{ route('subcon.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back</a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form action="{{ route('subcon.store') }}" method="POST" class="space-y-6 max-w-2xl">
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
                    <p class="mt-1 text-xs text-slate-500">Wajib diisi saat WH kirim ke vendor.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">WIP Part (Hasil Vendor) <span class="text-red-500">*</span></label>
                    <select name="gci_part_id" required x-model="selectedPartId" @change="onPartChange()"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select WIP Part</option>
                        @foreach ($subconParts as $p)
                            <option value="{{ $p['id'] }}"
                                data-process="{{ $p['process_name'] }}"
                                data-bom="{{ $p['bom_item_id'] }}"
                                data-rm-id="{{ $p['rm_part_id'] }}"
                                @selected(old('gci_part_id') == $p['id'])>
                                {{ $p['part_no'] }} - {{ $p['part_name'] }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="bom_item_id" x-model="bomItemId">
                    <p class="mt-1 text-xs text-slate-500">Part ini adalah hasil WIP yang akan diterima kembali dari vendor.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">RM Part (Barang Dikirim ke Vendor) <span class="text-red-500">*</span></label>
                    <select name="rm_gci_part_id" required x-model="rmPartId"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select RM Part</option>
                        @foreach ($rmParts as $p)
                            <option value="{{ $p['rm_part_id'] }}" @selected(old('rm_gci_part_id') == $p['rm_part_id'])>
                                {{ $p['rm_part_no'] ?? '-' }} - {{ $p['rm_part_name'] ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">RM part ini yang akan dipotong dari inventory saat WH send ke vendor.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Process Type <span class="text-red-500">*</span></label>
                    <input type="text" name="process_type" x-model="processType" required
                        placeholder="e.g. plating, hardening"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Qty Sent <span class="text-red-500">*</span></label>
                    <input type="number" name="qty_sent" step="0.0001" min="0.0001" required
                        value="{{ old('qty_sent') }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <div class="grid grid-cols-2 gap-4">
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
                    <label class="block text-sm font-bold text-slate-700 mb-1">WH Send Location <span class="text-red-500">*</span></label>
                    <input type="text" name="send_location_code" required value="{{ old('send_location_code') }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Contoh: RM-A01" />
                    <p class="mt-1 text-xs text-slate-500">Lokasi warehouse asal untuk RM part yang dikirim ke vendor.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                        Create WH Send
                    </button>
                    <a href="{{ route('subcon.index') }}"
                        class="rounded-lg bg-slate-100 px-6 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function subconCreate() {
            return {
                selectedPartId: '{{ old('gci_part_id', '') }}',
                rmPartId: '{{ old('rm_gci_part_id', '') }}',
                processType: '{{ old('process_type', '') }}',
                bomItemId: '{{ old('bom_item_id', '') }}',
                onPartChange() {
                    const select = document.querySelector('select[name="gci_part_id"]');
                    const option = select.options[select.selectedIndex];
                    if (option && option.dataset.process) {
                        this.processType = option.dataset.process;
                    }
                    if (option && option.dataset.bom) {
                        this.bomItemId = option.dataset.bom;
                    }
                    if (option && option.dataset.rmId) {
                        this.rmPartId = option.dataset.rmId;
                    }
                }
            }
        }
    </script>
@endsection
