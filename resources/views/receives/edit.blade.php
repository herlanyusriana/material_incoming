<x-app-layout>
    <x-slot name="header">
        Edit Receive — {{ $arrival->invoice_no ?? '-' }}
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm text-slate-600">{{ $arrival->vendor->vendor_name ?? '-' }} • {{ $arrival->invoice_no ?? '-' }}</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ $arrivalItem->part->part_no }} — {{ $arrivalItem->part->part_name_gci ?? $arrivalItem->part->part_name_vendor }}</div>
                        <div class="text-xs font-mono text-slate-500">{{ $arrivalItem->size ?? '-' }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('receives.label', $receive) }}" target="_blank" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg">Print Label</a>
                        <a href="{{ route('receives.completed.invoice', $arrival) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">Back</a>
                    </div>
                </div>
            </div>

            <form action="{{ route('receives.update', $receive) }}" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Tanggal Receive</label>
                        <input type="date" name="receive_date" value="{{ old('receive_date', optional($receive->ata_date)->toDateString() ?? now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                        @error('receive_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">QC Status</label>
                        <select name="qc_status" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="pass" @selected(old('qc_status', $receive->qc_status) === 'pass')>Good (Pass)</option>
                            <option value="reject" @selected(old('qc_status', $receive->qc_status) === 'reject')>No Good (Reject)</option>
                        </select>
                        @error('qc_status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">TAG (fisik)</label>
                        <input type="text" name="tag" value="{{ old('tag', $receive->tag) }}" class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="TAG-001">
                        @error('tag') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Storage Location</label>
                        <input type="text" name="location_code" value="{{ old('location_code', $receive->location_code) }}" class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="RACK-A1">
                        @error('location_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Package Qty</label>
                        <input type="number" name="bundle_qty" min="0" value="{{ old('bundle_qty', $receive->bundle_qty ?? 0) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                        @error('bundle_qty') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Package Unit</label>
                        <select name="bundle_unit" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="PALLET" @selected(old('bundle_unit', $receive->bundle_unit) === 'PALLET')>PALLET</option>
                            <option value="BUNDLE" @selected(old('bundle_unit', $receive->bundle_unit) === 'BUNDLE')>BUNDLE</option>
                            <option value="BOX" @selected(old('bundle_unit', $receive->bundle_unit) === 'BOX')>BOX</option>
                        </select>
                        @error('bundle_unit') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Qty Goods ({{ strtoupper($arrivalItem->unit_goods ?? 'KGM') }})</label>
                        <input type="number" name="qty" min="1" value="{{ old('qty', $receive->qty) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                        @error('qty') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Net Weight (KGM)</label>
                        <input type="number" step="0.01" name="net_weight" value="{{ old('net_weight', $receive->net_weight ?? $receive->weight) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="0.00">
                        @error('net_weight') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Gross Weight (KGM)</label>
                        <input type="number" step="0.01" name="gross_weight" value="{{ old('gross_weight', $receive->gross_weight) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="0.00">
                        @error('gross_weight') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="{{ route('receives.completed.invoice', $arrival) }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors text-sm font-medium">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        Update Receive
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
