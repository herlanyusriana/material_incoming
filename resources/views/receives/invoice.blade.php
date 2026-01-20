<x-app-layout>
    <x-slot name="header">
        Receive Invoice {{ $arrival->invoice_no }}
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <form action="{{ route('receives.invoice.store', $arrival) }}" method="POST" enctype="multipart/form-data" class="bg-white border border-slate-200 rounded-2xl shadow-lg p-8 space-y-8" id="receive-form">
                @csrf

                <div class="flex items-center justify-between pb-6 border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Receive Multiple Items</h3>
                        <p class="text-sm text-slate-600 mt-1">Proses semua item pending dalam satu invoice</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';
                            $hasContainerInspection = ($arrival->containers ?? collect())->contains(fn ($c) => (bool) $c->inspection);
                        @endphp
                        @if (!$isLocal && $hasContainerInspection)
                            <a href="{{ route('departures.inspection-report', $arrival) }}" target="_blank" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Print Inspection</a>
                        @endif
                        <a href="{{ route('receives.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Kembali</a>
                    </div>
                </div>

                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">{{ $isLocal ? 'Info Local PO' : 'Info Invoice' }}</h4>
                    <div class="grid md:grid-cols-2 gap-x-12 gap-y-4 text-sm">
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Supplier</span>
                            <span class="text-slate-900">= {{ $arrival->vendor->vendor_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Invoice No.</span>
                            <span class="text-slate-900">= {{ $arrival->invoice_no }}</span>
                        </div>
                        @if ($isLocal)
                            <div class="flex items-center">
                                <span class="font-semibold text-slate-700 w-32">PO Date</span>
                                <span class="text-slate-900">= {{ $arrival->invoice_date ? $arrival->invoice_date->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-semibold text-slate-700 w-32">Currency</span>
                                <span class="text-slate-900">= {{ $arrival->currency ?? 'IDR' }}</span>
                            </div>
                        @else
                            <div class="flex items-center">
                                <span class="font-semibold text-slate-700 w-32">ETD</span>
                                <span class="text-slate-900">= {{ $arrival->ETD ? $arrival->ETD->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-semibold text-slate-700 w-32">ETA</span>
                                <span class="text-slate-900">= {{ $arrival->ETA ? $arrival->ETA->format('d M Y') : '-' }}</span>
                            </div>
                        @endif
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Total Bundle</span>
                            <span class="text-slate-900">= {{ number_format($pendingItems->sum('qty_bundle') ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                @if ($isLocal)
                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Upload Documents</h4>
                         <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Surat Jalan</label>
                                <input type="file" name="delivery_note_file"
                                    class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100" accept=".pdf,.jpg,.jpeg,.png">
                                @error('delivery_note_file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Invoice</label>
                                <input type="file" name="invoice_file"
                                    class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100" accept=".pdf,.jpg,.jpeg,.png">
                                @error('invoice_file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Packing List</label>
                                <input type="file" name="packing_list_file"
                                    class="block w-full text-sm text-slate-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100" accept=".pdf,.jpg,.jpeg,.png">
                                @error('packing_list_file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                         </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Input Mode</h4>
                        <div class="flex flex-wrap items-center gap-6 text-sm">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="tag_mode" value="no_tag" class="rounded border-slate-300" @checked(old('tag_mode', 'no_tag') === 'no_tag')>
                                <span class="font-semibold text-slate-800">No TAG (1 form / item)</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="tag_mode" value="with_tag" class="rounded border-slate-300" @checked(old('tag_mode') === 'with_tag')>
                                <span class="font-semibold text-slate-800">With TAG (auto generate per package)</span>
                            </label>
                            <div class="text-xs text-slate-500">
                                No TAG: input sekali total package + total qty. With TAG: TAG otomatis dibuat sesuai jumlah package.
                            </div>
                        </div>
                        @error('tag_mode') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="bg-white rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Receiving Info</h4>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                        <div class="space-y-1">
                            <label for="receive_date" class="text-sm font-medium text-slate-700">Tanggal Receive</label>
                            <input type="date" id="receive_date" name="receive_date" value="{{ old('receive_date', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            @error('receive_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="invoice_no" class="text-sm font-medium text-slate-700">Invoice No.</label>
                            <input type="text" id="invoice_no" name="invoice_no" value="{{ old('invoice_no', $arrival->invoice_no) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm uppercase" placeholder="INV/2024/001">
                            @error('invoice_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="delivery_note_no" class="text-sm font-medium text-slate-700">No. Surat Jalan</label>
                            <input type="text" id="delivery_note_no" name="delivery_note_no" value="{{ old('delivery_note_no') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm uppercase" placeholder="SJ/2024/001">
                            @error('delivery_note_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($isLocal)
                            <div class="space-y-1">
                                <label for="truck_no" class="text-sm font-medium text-slate-700">No. Truck</label>
                                <input type="text" id="truck_no" name="truck_no" value="{{ old('truck_no') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm uppercase" placeholder="B 1234 CD" required>
                                @error('truck_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                @php
                    $containers = $arrival->containers ?? collect();
                @endphp
                @if (!$isLocal && $containers->count())
                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Container Inspection (per Container)</h4>
                        <div class="overflow-x-auto border border-slate-200 rounded-xl">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                        <th class="px-4 py-3 text-left font-semibold">Container No</th>
                                        <th class="px-4 py-3 text-left font-semibold">Seal Code</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($containers as $c)
                                        <tr>
                                            <td class="px-4 py-3 font-mono text-xs">{{ strtoupper($c->container_no) }}</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ strtoupper($c->seal_code ?? '-') }}</td>
                                            <td class="px-4 py-3">
                                                @if ($c->inspection)
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold border {{ $c->inspection->status === 'damage' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                                        {{ strtoupper($c->inspection->status) }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                                                        NOT INSPECTED
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-xs text-slate-500">
                            Input inspection dilakukan dari aplikasi mobile (per container). Halaman receive hanya menampilkan status + print report.
                        </div>
                    </div>
                @endif

                @foreach($pendingItems as $item)
                    <div class="border border-slate-200 rounded-xl shadow-sm">
                        <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-slate-100 flex items-center justify-between">
                            <div class="space-y-1 text-sm">
                                <div class="text-xs uppercase text-slate-500">Item</div>
                                <div class="font-semibold text-slate-900">{{ $item->part->part_no }} — {{ $item->part->part_name_gci ?? $item->part->part_name_vendor }}</div>
                                <div class="text-slate-600 font-mono text-xs">{{ $item->size ?? '-' }}</div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Planned</span>
                                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800">{{ number_format($item->qty_goods) }}</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ strtoupper($item->unit_goods ?? 'KGM') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Remaining</span>
                                    <span class="px-2 py-1 rounded-lg bg-green-50 text-green-800" id="remaining-{{ $item->id }}">{{ number_format($item->remaining_qty) }}</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ strtoupper($item->unit_goods ?? 'KGM') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Input Total</span>
                                    <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-800" id="input-total-{{ $item->id }}">0</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ strtoupper($item->unit_goods ?? 'KGM') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Total Bundle</span>
                                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800">{{ number_format($item->qty_bundle ?? 0) }}</span>
                                    <span class="text-xs font-semibold text-slate-500">{{ strtoupper($item->unit_bundle ?? 'PALLET') }}</span>
                                </div>
                                @if($item->weight_nett > 0 || $item->weight_gross > 0)
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-700">Planned Weight</span>
                                        <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800 text-xs">
                                            N: {{ number_format($item->weight_nett, 1) }} / G: {{ number_format($item->weight_gross, 1) }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-500">KGM</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="overflow-x-auto tag-mode">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                            Tag
                                            <button type="button" class="ml-2 px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors shadow-sm add-tag-btn" data-item="{{ $item->id }}">+ Add TAG</button>
                                        </th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Bundle</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Qty Goods</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Net Weight (KGM)</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Gross Weight (KGM)</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">QC</th>
	                                    </tr>
	                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 bg-white tag-rows"
                                    data-item="{{ $item->id }}"
                                    data-default-weight="{{ $item->default_weight }}"
                                    data-bundles="{{ $item->qty_bundle ?? 0 }}"
                                    data-size="{{ $item->size ?? '-' }}"
                                    data-part-no="{{ $item->part->part_no }}"
                                    data-part-name="{{ $item->part->part_name_gci ?? $item->part->part_name_vendor }}"
                                    data-goods-unit="{{ strtoupper($item->unit_goods ?? 'KGM') }}"
                                >
                                    <tr class="tag-row hover:bg-slate-50 transition-colors">
                                        <td class="px-3 py-2 align-top">
                                            <input type="text" name="items[{{ $item->id }}][tags][0][tag]" placeholder="TAG-001" class="w-40 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                        </td>
	                                        <td class="px-3 py-2 align-top">
	                                            <input type="text" name="items[{{ $item->id }}][tags][0][location_code]" placeholder="RACK-A1" class="w-32 uppercase rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" data-qr-location-input>
	                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            @php
                                                $defaultBundleUnit = strtoupper($item->unit_bundle ?? 'PALLET');
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="items[{{ $item->id }}][tags][0][bundle_qty]" min="0" value="0" class="w-16 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                <select name="items[{{ $item->id }}][tags][0][bundle_unit]" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                    <option value="PALLET" @selected($defaultBundleUnit === 'PALLET')>PALLET</option>
                                                    <option value="BUNDLE" @selected($defaultBundleUnit === 'BUNDLE')>BUNDLE</option>
                                                    <option value="BOX" @selected($defaultBundleUnit === 'BOX')>BOX</option>
                                                    <option value="BAG" @selected($defaultBundleUnit === 'BAG')>BAG</option>
                                                    <option value="ROLL" @selected($defaultBundleUnit === 'ROLL')>ROLL</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            @php $defaultGoodsUnit = strtoupper($item->unit_goods ?? 'KGM'); @endphp
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="items[{{ $item->id }}][tags][0][qty]" min="1" placeholder="Qty {{ $defaultGoodsUnit }}" class="qty-input w-24 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required data-item="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $item->id }}][tags][0][qty_unit]" value="{{ $defaultGoodsUnit }}">
                                                <span class="w-24 text-center text-xs font-semibold text-slate-700">{{ $defaultGoodsUnit }}</span>
                                            </div>
                                        </td>
	                                        <td class="px-3 py-2 align-top">
	                                            <input
	                                                type="number"
	                                                name="items[{{ $item->id }}][tags][0][net_weight]"
	                                                step="0.01"
	                                                placeholder="0.00"
	                                                class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5"
	                                            >
	                                        </td>
	                                        <td class="px-3 py-2 align-top">
	                                            <input
	                                                type="number"
	                                                name="items[{{ $item->id }}][tags][0][gross_weight]"
	                                                step="0.01"
	                                                placeholder="0.00"
	                                                class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5"
	                                            >
	                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <select name="items[{{ $item->id }}][tags][0][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                <option value="pass">Pass</option>
                                                <option value="reject">Reject</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if ($isLocal)
                            <div class="overflow-x-auto no-tag-mode">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Bundle</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Qty Goods</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Net Weight (KGM)</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Gross Weight (KGM)</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">QC</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-3 py-2 align-top">
                                                <input type="text" name="items[{{ $item->id }}][summary][location_code]" placeholder="RACK-A1" class="w-32 uppercase rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" data-qr-location-input>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                @php
                                                    $defaultBundleUnit = strtoupper($item->unit_bundle ?? 'PALLET');
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                    <input type="number" name="items[{{ $item->id }}][summary][bundle_qty]" min="0" value="{{ (int) ($item->qty_bundle ?? 0) }}" class="w-16 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                    <select name="items[{{ $item->id }}][summary][bundle_unit]" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                        <option value="PALLET" @selected($defaultBundleUnit === 'PALLET')>PALLET</option>
                                                        <option value="BUNDLE" @selected($defaultBundleUnit === 'BUNDLE')>BUNDLE</option>
                                                        <option value="BOX" @selected($defaultBundleUnit === 'BOX')>BOX</option>
                                                        <option value="BAG" @selected($defaultBundleUnit === 'BAG')>BAG</option>
                                                        <option value="ROLL" @selected($defaultBundleUnit === 'ROLL')>ROLL</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                @php $defaultGoodsUnit = strtoupper($item->unit_goods ?? 'KGM'); @endphp
                                                <div class="flex items-center gap-2">
                                                    <input type="number" name="items[{{ $item->id }}][summary][qty]" min="0" placeholder="0" class="no-tag-qty w-24 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required data-item="{{ $item->id }}">
                                                    <input type="hidden" name="items[{{ $item->id }}][summary][qty_unit]" value="{{ $defaultGoodsUnit }}">
                                                    <span class="w-24 text-center text-xs font-semibold text-slate-700">{{ $defaultGoodsUnit }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" name="items[{{ $item->id }}][summary][net_weight]" step="0.01" placeholder="0.00" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5">
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input type="number" name="items[{{ $item->id }}][summary][gross_weight]" step="0.01" placeholder="0.00" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5">
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <select name="items[{{ $item->id }}][summary][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                    <option value="pass">Pass</option>
                                                    <option value="reject">Reject</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-200">
                    <a href="{{ route('receives.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors text-sm font-medium">Cancel</a>
                    <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Receive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const isLocal = {{ $isLocal ? 'true' : 'false' }};
        function getTagMode() {
            const checked = document.querySelector('input[name="tag_mode"]:checked');
            return checked ? checked.value : null;
        }

        const tagIndexes = {};
        const remainingMap = {};
        const inputTotals = {};

        document.querySelectorAll('.tag-rows').forEach(tbody => {
            const itemId = tbody.dataset.item;
            tagIndexes[itemId] = 1;
            remainingMap[itemId] = Number(document.getElementById(`remaining-${itemId}`).textContent.replace(/,/g, '')) || 0;
            inputTotals[itemId] = 0;
        });

        function applyModeUi() {
            if (!isLocal) return;
            const mode = getTagMode() || 'no_tag';

            const setSectionEnabled = (sectionEl, enabled) => {
                if (!sectionEl) return;
                sectionEl.classList.toggle('hidden', !enabled);
                sectionEl.querySelectorAll('input, select, textarea, button').forEach(el => {
                    // Keep radio buttons working
                    if (el instanceof HTMLInputElement && el.type === 'radio') return;
                    // Keep "+ Add TAG" button hidden section disabled too
                    el.disabled = !enabled;
                });
            };

            document.querySelectorAll('.tag-mode').forEach(el => setSectionEnabled(el, mode === 'with_tag'));
            document.querySelectorAll('.no-tag-mode').forEach(el => setSectionEnabled(el, mode === 'no_tag'));
        }

        function updateTotals(itemId) {
            const rows = document.querySelectorAll(`.tag-rows[data-item="${itemId}"] input.qty-input`);
            let total = 0;
            rows.forEach(input => {
                total += Number(input.value) || 0;
            });
            inputTotals[itemId] = total;
            const totalEl = document.getElementById(`input-total-${itemId}`);
            if (totalEl) totalEl.textContent = total;

            let alertEl = document.getElementById(`alert-${itemId}`);
            if (!alertEl) {
                alertEl = document.createElement('div');
                alertEl.id = `alert-${itemId}`;
                alertEl.className = 'px-4 py-2 text-sm font-semibold';
                document.querySelector(`.tag-rows[data-item="${itemId}"]`).parentElement.appendChild(alertEl);
            }

            if (total > remainingMap[itemId]) {
                alertEl.textContent = `Total qty (${total}) melebihi sisa (${remainingMap[itemId]}).`;
                alertEl.classList.remove('hidden');
                alertEl.classList.add('text-red-600');
            } else {
                alertEl.textContent = '';
                alertEl.classList.add('hidden');
            }
        }

        function bindRowEvents(row, itemId) {
            const qtyInput = row.querySelector('input.qty-input');
            if (qtyInput) {
                qtyInput.addEventListener('input', () => updateTotals(itemId));
            }
            const removeBtn = row.querySelector('.remove-tag');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    row.remove();
                    updateTotals(itemId);
                });
            }
        }

	        function createTagRowHtml(itemId, idx, sizeText, partNo, partName, defaultWeight, goodsUnit) {
	            return `
	                <td class="px-3 py-2 align-top">
	                    <input type="text" name="items[${itemId}][tags][${idx}][tag]" placeholder="TAG-${String(idx + 1).padStart(3, '0')}" class="w-40 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
	                </td>
	                <td class="px-3 py-2 align-top">
	                    <input type="text" name="items[${itemId}][tags][${idx}][location_code]" placeholder="RACK-A1" class="w-32 uppercase rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" data-qr-location-input>
	                </td>
	                <td class="px-3 py-2 align-top">
                        <div class="flex items-center gap-2">
	                        <input type="number" name="items[${itemId}][tags][${idx}][bundle_qty]" min="0" value="0" class="w-16 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
	                            <select name="items[${itemId}][tags][${idx}][bundle_unit]" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
	                                <option value="PALLET">PALLET</option>
	                                <option value="BUNDLE">BUNDLE</option>
	                                <option value="BOX">BOX</option>
	                                <option value="BAG">BAG</option>
	                                <option value="ROLL">ROLL</option>
	                            </select>
                        </div>
	                </td>
		                <td class="px-3 py-2 align-top">
	                        <div class="flex items-center gap-2">
		                        <input type="number" name="items[${itemId}][tags][${idx}][qty]" min="1" value="1" placeholder="Qty ${goodsUnit}" class="qty-input w-24 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required data-item="${itemId}">
	                            <input type="hidden" name="items[${itemId}][tags][${idx}][qty_unit]" value="${goodsUnit}">
	                            <span class="w-24 text-center text-xs font-semibold text-slate-700">${goodsUnit}</span>
	                        </div>
		                </td>
	                <td class="px-3 py-2 align-top">
	                    <input type="number" name="items[${itemId}][tags][${idx}][net_weight]" step="0.01" placeholder="0.00" value="${defaultWeight || ''}" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5">
	                </td>
	                <td class="px-3 py-2 align-top">
	                    <input type="number" name="items[${itemId}][tags][${idx}][gross_weight]" step="0.01" placeholder="0.00" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5">
	                </td>
                <td class="px-3 py-2 align-top">
                    <select name="items[${itemId}][tags][${idx}][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                        <option value="pass">Pass</option>
                        <option value="reject">Reject</option>
                    </select>
                </td>
            `;
        }

        document.querySelectorAll('.add-tag-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (isLocal && (getTagMode() || 'no_tag') !== 'with_tag') return;
                const itemId = btn.dataset.item;
                const tbody = document.querySelector(`.tag-rows[data-item="${itemId}"]`);
                const idx = tagIndexes[itemId] ?? 0;
                const defaultWeight = tbody.dataset.defaultWeight;
                const sizeText = tbody.dataset.size || '';
                const partNo = tbody.dataset.partNo || '';
                const partName = tbody.dataset.partName || '';
                const goodsUnit = tbody.dataset.goodsUnit || 'KGM';

                const row = document.createElement('tr');
                row.className = 'tag-row hover:bg-slate-50 transition-colors';
                row.innerHTML = createTagRowHtml(itemId, idx, sizeText, partNo, partName, defaultWeight, goodsUnit);
                tbody.appendChild(row);
                tagIndexes[itemId] = idx + 1;
                bindRowEvents(row, itemId);
                updateTotals(itemId);
            });
        });

        function initTagTablesIfNeeded() {
            const mode = isLocal ? (getTagMode() || 'no_tag') : 'with_tag';
            if (mode !== 'with_tag') return;

            document.querySelectorAll('.tag-rows').forEach(tbody => {
                const itemId = tbody.dataset.item;
                const bundleCount = Number(tbody.dataset.bundles || '0');
                const firstNetWeightInput = tbody.querySelector('input[name$="[net_weight]"], input[name$="[weight]"]');
                tbody.dataset.defaultWeight = firstNetWeightInput ? firstNetWeightInput.value || '' : '';

                const sizeText = tbody.dataset.size || '';
                const partNo = tbody.dataset.partNo || '';
                const partName = tbody.dataset.partName || '';
                const defaultWeight = tbody.dataset.defaultWeight;
                const goodsUnit = tbody.dataset.goodsUnit || 'KGM';

                tbody.innerHTML = '';

                const rowsToCreate = bundleCount > 0 ? bundleCount : 1;

                for (let idx = 0; idx < rowsToCreate; idx++) {
                    const row = document.createElement('tr');
                    row.className = 'tag-row hover:bg-slate-50 transition-colors';
                    row.innerHTML = createTagRowHtml(itemId, idx, sizeText, partNo, partName, defaultWeight, goodsUnit);
                    tbody.appendChild(row);
                    bindRowEvents(row, itemId);
                }

                if (isLocal) {
                    tbody.querySelectorAll('input[name*="[tag]"]').forEach((input, i) => {
                        if (input.value && String(input.value).trim() !== '') return;
                        input.value = `${String(itemId).padStart(3, '0')}-${String(i + 1).padStart(3, '0')}`;
                    });
                }

                tagIndexes[itemId] = rowsToCreate;
                updateTotals(itemId);
            });
        }

        function initNoTagTotals() {
            if (!isLocal) return;
            document.querySelectorAll('input.no-tag-qty').forEach(input => {
                const itemId = input.dataset.item;
                input.addEventListener('input', () => {
                    const totalEl = document.getElementById(`input-total-${itemId}`);
                    const value = Number(input.value) || 0;
                    if (totalEl) totalEl.textContent = value;
                });
            });
        }

        if (isLocal) {
            document.querySelectorAll('input[name="tag_mode"]').forEach(r => {
                r.addEventListener('change', () => {
                    applyModeUi();
                    initTagTablesIfNeeded();
                });
            });
        }

        applyModeUi();
        initNoTagTotals();
        initTagTablesIfNeeded();

        document.getElementById('receive-form').addEventListener('submit', function (e) {
            let hasError = false;
            const mode = isLocal ? (getTagMode() || 'no_tag') : 'with_tag';
            if (mode === 'with_tag') {
                Object.keys(remainingMap).forEach(itemId => {
                    updateTotals(itemId);
                    if (inputTotals[itemId] > remainingMap[itemId]) {
                        hasError = true;
                    }
                });
            } else if (mode === 'no_tag') {
                document.querySelectorAll('input.no-tag-qty').forEach(input => {
                    const itemId = input.dataset.item;
                    const value = Number(input.value) || 0;
                    if (value > (remainingMap[itemId] || 0)) {
                        hasError = true;
                    }
                });
            }
            if (hasError) {
                e.preventDefault();
                alert('Ada qty yang melebihi sisa. Mohon periksa kembali.');
            }
        });
    </script>
</x-app-layout>
