<x-app-layout>
    <x-slot name="header">
        Departure {{ $arrival->arrival_no }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Back Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('departures.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back to Departures</span>
                </a>
                <div class="flex items-center gap-2">
                    <a href="{{ route('receives.invoice.create', $arrival) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg transition-colors">
                        Receive Invoice
                    </a>
                    @if ($arrival->inspection)
                        <a href="{{ route('departures.inspection-report', $arrival) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                            Print Inspection
                        </a>
                    @endif
                    <a href="{{ route('departures.invoice', $arrival) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2Zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10Z" />
                        </svg>
                        Print Invoice
                    </a>
                </div>
            </div>
            
            <!-- Departure Information -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Shipment Information</h3>
                    <p class="text-sm text-slate-600 mt-1">Vendor {{ $arrival->vendor->vendor_name ?? '-' }} • Invoice {{ $arrival->invoice_no }}</p>
                </div>
                @php
                    $truckingName = $arrival->trucking?->company_name ?: ($arrival->trucking_company ?: null);

                    $containerNumbers = $arrival->containers->pluck('container_no')->filter()->values();
                    if ($containerNumbers->isEmpty() && $arrival->container_numbers) {
                        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $arrival->container_numbers) ?: [])
                            ->map(fn ($line) => trim((string) $line))
                            ->filter()
                            ->values();
                        $containerNumbers = $lines;
                    }
                    $containerSummary = $containerNumbers->map(fn ($no) => strtoupper((string) $no))->implode(', ');

                    $hsCodeDisplay = $arrival->hs_code
                        ?: $arrival->items
                            ->pluck('part.hs_code')
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode(', ');
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Invoice:</span>
                        <span class="text-slate-900">{{ $arrival->invoice_no }} ({{ $arrival->invoice_date->format('Y-m-d') }})</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Vendor:</span>
                        <span class="text-slate-900">{{ $arrival->vendor->vendor_name ?? '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Created by:</span>
                        <span class="text-slate-900">{{ $arrival->creator->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Vessel:</span>
                        <span class="text-slate-900">{{ $arrival->vessel ?: '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ $truckingName ? 'Trucking:' : 'No. Container:' }}</span>
                        <span class="text-slate-900">{{ $truckingName ?: ($containerSummary ?: '-') }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">ETD:</span>
                        <span class="text-slate-900">{{ $arrival->ETD ?: '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Bill of Lading:</span>
                        <span class="text-slate-900">{{ $arrival->bill_of_lading ?: '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">HS Code:</span>
                        <span class="text-slate-900">{{ $hsCodeDisplay ?: '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Port of Loading:</span>
                        <span class="text-slate-900">{{ $arrival->port_of_loading ?: '-' }}</span>
                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">Container:</span>
	                        <span class="text-slate-900">
	                            @if ($arrival->containers->count())
	                                <div class="space-y-1">
	                                    @foreach ($arrival->containers as $idx => $c)
	                                        <div class="text-sm">
	                                            {{ $idx + 1 }}. {{ strtoupper($c->container_no) }}
	                                            @if ($c->seal_code)
	                                                <span class="text-slate-500">— SEAL {{ strtoupper($c->seal_code) }}</span>
	                                            @endif
	                                        </div>
	                                    @endforeach
	                                </div>
	                            @elseif ($arrival->container_numbers)
	                                <span class="whitespace-pre-line">{{ $arrival->container_numbers }}</span>
	                            @else
	                                -
	                            @endif
	                        </span>
	                    </div>
	                    @if (! $arrival->containers->count())
	                        <div class="flex items-start gap-2">
	                            <span class="font-semibold text-slate-700 min-w-[100px]">Seal Code:</span>
	                            <span class="text-slate-900">{{ $arrival->seal_code ?: '-' }}</span>
	                        </div>
	                    @endif
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Currency:</span>
                        <span class="text-slate-900">{{ $arrival->currency }}</span>
                    </div>
                </div>
                @if ($arrival->notes)
                    <div class="flex items-start gap-2 pt-2 border-t border-slate-200 text-sm">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Notes:</span>
                        <span class="text-slate-900">{{ $arrival->notes }}</span>
                    </div>
                @endif
            </div>

            <!-- Inspection Summary (read-only) -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Container Inspection</h3>
                    <p class="text-sm text-slate-600 mt-1">Diinput dari aplikasi Android (read-only di web).</p>
                </div>

                @if ($arrival->inspection)
                    @php
                        $statusColor = $arrival->inspection->status === 'damage'
                            ? 'bg-red-100 text-red-700'
                            : 'bg-green-100 text-green-700';
                        $photos = [
                            'Left' => $arrival->inspection->photo_left,
                            'Right' => $arrival->inspection->photo_right,
                            'Front' => $arrival->inspection->photo_front,
                            'Back' => $arrival->inspection->photo_back,
                        ];
                        $issuesMap = [
                            'Left' => $arrival->inspection->issues_left ?? [],
                            'Right' => $arrival->inspection->issues_right ?? [],
                            'Front' => $arrival->inspection->issues_front ?? [],
                            'Back' => $arrival->inspection->issues_back ?? [],
                        ];
                    @endphp

                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                            {{ strtoupper($arrival->inspection->status) }}
                        </span>
                        <div class="text-sm text-slate-600">
                            Updated: {{ $arrival->inspection->updated_at?->format('Y-m-d H:i') ?? '-' }}
                        </div>
                    </div>

                    @if ($arrival->inspection->notes)
                        <div class="text-sm text-slate-800">
                            <span class="font-semibold text-slate-700">Notes:</span> {{ $arrival->inspection->notes }}
                        </div>
                    @endif

                    <div class="space-y-3">
                        @foreach ($issuesMap as $side => $issues)
                            @if (!empty($issues))
                                <div class="text-sm">
                                    <div class="font-semibold text-slate-700">{{ $side }}</div>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach ($issues as $issue)
                                            <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                                {{ $issue }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach ($photos as $label => $path)
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700">{{ $label }}</div>
                                @if ($path)
                                    <img src="{{ Storage::url($path) }}" alt="{{ $label }}" class="w-full h-40 object-cover">
                                @else
                                    <div class="h-40 flex items-center justify-center text-sm text-slate-400">No photo</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-slate-600">
                        Belum ada data inspeksi untuk invoice ini.
                    </div>
                @endif
            </div>

            <!-- Items Table -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Departure Items</h3>
                    <p class="text-sm text-slate-600 mt-1">Parts and receiving details</p>
                </div>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Part</th>
                                <th class="px-4 py-3 text-left font-semibold">Size</th>
                                <th class="px-4 py-3 text-left font-semibold">Qty Bundle</th>
                                <th class="px-4 py-3 text-left font-semibold">Qty Goods</th>
                                <th class="px-4 py-3 text-left font-semibold">Nett (kg)</th>
                                <th class="px-4 py-3 text-left font-semibold">Gross (kg)</th>
                                <th class="px-4 py-3 text-left font-semibold">Price</th>
                                <th class="px-4 py-3 text-left font-semibold">Total</th>
                                <th class="px-4 py-3 text-left font-semibold">Received</th>
                                <th class="px-4 py-3 text-center font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($arrival->items as $item)
                                @php
                                    $receivedQty = $item->receives->sum('qty');
                                    $remainingQty = max(0, ($item->qty_goods ?? 0) - $receivedQty);
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 text-slate-800">
                                        <div class="font-semibold">{{ $item->part->part_no }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->part->part_name_vendor }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700 font-mono text-xs">{{ $item->size ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $item->qty_bundle }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $item->qty_goods }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ number_format($item->weight_nett, 0) }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ number_format($item->weight_gross, 0) }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ number_format($item->price, 3) }}</td>
                                    <td class="px-4 py-4 text-slate-800 font-semibold">{{ number_format($item->total_price, 2) }}</td>
                                    <td class="px-4 py-4">
                                        <div class="text-slate-800 font-semibold">{{ number_format($receivedQty) }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->receives->count() }} receive{{ $item->receives->count() != 1 ? 's' : '' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-center">
                                            @if ($remainingQty > 0)
                                                <a href="{{ route('receives.create', $item) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                    Receive
                                                </a>
                                            @else
                                                <span class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-600 text-sm font-semibold rounded-lg">
                                                    Complete
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
