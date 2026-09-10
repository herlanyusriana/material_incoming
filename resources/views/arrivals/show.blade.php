<x-app-layout>
    <x-slot name="header">
        {{ __('incoming.arrivals.show.header', ['invoice' => ($arrival->invoice_no ?? 'Detail')]) }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Back Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('departures.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>{{ __('incoming.arrivals.show.back') }}</span>
                </a>
                <div class="flex items-center gap-2">
                    <a href="{{ route('receives.completed.invoice', $arrival) }}" class="inline-flex items-center gap-2 px-4 py-2 {{ ($isReceiveComplete ?? false) ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800' : 'bg-slate-100 hover:bg-slate-200 text-slate-800' }} font-medium rounded-lg transition-colors">
                        {{ __('incoming.arrivals.show.receive_summary') }}
                    </a>
                    @if (!($isReceiveComplete ?? false))
                        <a href="{{ route('receives.invoice.create', $arrival) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg transition-colors">
                            {{ __('incoming.arrivals.show.receive_invoice') }}
                        </a>
                    @endif
                    <a href="{{ route('departures.edit', $arrival) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium rounded-lg transition-colors">
                        {{ __('incoming.arrivals.show.edit_departure') }}
                    </a>
                    @php
                        $hasContainerInspection = ($arrival->containers ?? collect())->contains(fn ($c) => (bool) $c->inspection);
                    @endphp
                    @if ($arrival->inspection || $hasContainerInspection)
                        <a href="{{ route('departures.inspection-report', $arrival) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                            {{ __('incoming.arrivals.show.print_inspection') }}
                        </a>
                    @endif
                    <a href="{{ route('departures.export-detail', $arrival) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 12 12 16.5m0 0L16.5 12M12 16.5V3" />
                        </svg>
                        {{ __('incoming.arrivals.show.export_excel') }}
                    </a>
                    <a href="{{ route('departures.invoice', $arrival) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2Zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10Z" />
                        </svg>
                        {{ __('incoming.arrivals.show.print_invoice') }}
                    </a>
                </div>
            </div>
            
            <!-- Departure Information -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">{{ __('incoming.arrivals.show.shipment_info') }}</h3>
                    <p class="text-sm text-slate-600 mt-1">Vendor {{ $arrival->vendor->vendor_name ?? '-' }} • Invoice {{ $arrival->invoice_no }}</p>
                </div>
	                @php
	                    $truckingName = $arrival->trucking?->company_name ?: ($arrival->trucking_company ?: null);

	                    $containerDetails = collect();
	                    if ($arrival->containers->count()) {
	                        $containerDetails = $arrival->containers
	                            ->map(function ($c) {
	                                $containerNo = strtoupper(trim((string) ($c->container_no ?? '')));
	                                $sealCode = strtoupper(trim((string) ($c->seal_code ?? '')));
	                                return [
	                                    'container_no' => $containerNo,
	                                    'seal_code' => $sealCode !== '' ? $sealCode : null,
	                                ];
	                            })
	                            ->filter(fn ($row) => $row['container_no'] !== '')
	                            ->values();
	                    } elseif ($arrival->container_numbers) {
	                        $containerPattern = '/^[A-Z]{4}\\d{7}$/';
	                        $tokens = collect(preg_split('/[\\s,;]+/', (string) $arrival->container_numbers) ?: [])
	                            ->map(fn ($t) => strtoupper(trim((string) $t)))
	                            ->filter()
	                            ->values();

	                        $defaultSeal = strtoupper(trim((string) ($arrival->seal_code ?? '')));
	                        $rows = [];
	                        $i = 0;
	                        while ($i < $tokens->count()) {
	                            $current = (string) $tokens[$i];
	                            if (!preg_match($containerPattern, $current)) {
	                                $i++;
	                                continue;
	                            }

	                            $next = $tokens->get($i + 1);
	                            $nextStr = $next !== null ? (string) $next : '';
	                            $nextIsContainer = $nextStr !== '' && preg_match($containerPattern, $nextStr);

	                            if ($nextIsContainer) {
	                                $rows[] = ['container_no' => $current, 'seal_code' => null];
	                                $i += 1;
	                                continue;
	                            }

	                            $seal = $nextStr !== '' ? $nextStr : ($defaultSeal !== '' ? $defaultSeal : null);
	                            $rows[] = ['container_no' => $current, 'seal_code' => $seal];
	                            $i += ($nextStr !== '') ? 2 : 1;
	                        }

	                        $containerDetails = collect($rows)
	                            ->unique('container_no')
	                            ->values();
	                    }
	                    $containerSummary = $containerDetails->pluck('container_no')->filter()->implode(', ');


	                        // We prioritize calculating HS codes directly from the items' parts to ensure accuracy.
	                        // Any duplicates are filtered out, as requested.
	                        $hsCodes = $arrival->items
	                            ->pluck('part.hs_code')
	                            ->filter(fn ($code) => trim((string) $code) !== '')
	                            ->flatMap(fn ($code) => collect(preg_split('/[\r\n,;]+/', (string) $code) ?: []))
	                            ->map(fn ($code) => strtoupper(trim((string) $code)))
	                            ->filter()
	                            ->unique()
	                            ->values();

	                        // If no items exist or items have no HS codes, fallback to the stored values in arrival.
	                        if ($hsCodes->isEmpty() && ($arrival->hs_codes || $arrival->hs_code)) {
	                            $hsCodes = collect(preg_split('/\r\n|\r|\n|,|;/', (string) ($arrival->hs_codes ?: $arrival->hs_code)) ?: [])
	                                ->map(fn ($v) => trim((string) $v))
	                                ->filter()
	                                ->unique()
	                                ->values();
	                        }
	                    @endphp
	                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.invoice_label') }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-900">{{ $arrival->invoice_no }} ({{ $arrival->invoice_date ? $arrival->invoice_date->format('Y-m-d') : '-' }})</span>
                            @if ($arrival->purchaseOrder)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700" title="Auto-generated from PO">
                                    PO: {{ $arrival->purchaseOrder->po_number }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.vendor') }}</span>
                        <span class="text-slate-900">{{ $arrival->vendor->vendor_name ?? '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.created_by') }}</span>
                        <span class="text-slate-900">{{ $arrival->creator->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.vessel') }}</span>
                        <span class="text-slate-900">{{ $arrival->vessel ?: '-' }}</span>
                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.trucking') }}</span>
	                        <span class="text-slate-900">{{ $truckingName ?: '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.no_container') }}</span>
	                        <span class="text-slate-900">{{ $containerSummary ?: '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.etd') }}</span>
	                        <span class="text-slate-900">{{ $arrival->ETD ? $arrival->ETD->format('Y-m-d') : '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.seal_code') }}</span>
	                        <span class="text-slate-900 font-semibold">{{ $arrival->seal_code ?: '-' }}</span>
	                    </div>
		                    <div class="flex items-start gap-2">
		                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.bl') }}</span>
		                        <span class="text-slate-900">
                                    {{ $arrival->bill_of_lading ?: '-' }}
                                    @if ($arrival->bill_of_lading_status)
                                        <span class="ml-2 inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                            {{ strtoupper($arrival->bill_of_lading_status) }}
                                        </span>
                                    @endif
                                    @if ($arrival->bill_of_lading_file_url)
                                        <a class="ml-2 text-xs font-semibold text-indigo-600 hover:underline" href="{{ $arrival->bill_of_lading_file_url }}" target="_blank" rel="noopener">
                                            {{ __('incoming.arrivals.show.view_file') }}
                                        </a>
                                    @endif
                                </span>
		                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.pen_no') }}</span>
	                        <span class="text-slate-900">{{ $arrival->pen_no ?: '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.pen_date') }}</span>
	                        <span class="text-slate-900">{{ $arrival->pen_date ? $arrival->pen_date->format('Y-m-d') : '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.aju_no') }}</span>
	                        <span class="text-slate-900">{{ $arrival->aju_no ?: '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.price_term') }}</span>
	                        <span class="text-slate-900">{{ $arrival->price_term ?: '-' }}</span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.hs_code') }}</span>
	                        <span class="text-slate-900">
	                            @if ($hsCodes->count())
	                                <div class="flex flex-wrap gap-1">
	                                    @foreach ($hsCodes as $code)
	                                        <span class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700">
	                                            {{ $code }}
	                                        </span>
	                                    @endforeach
	                                </div>
	                            @else
	                                -
	                            @endif
	                        </span>
	                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.port_loading') }}</span>
                        <span class="text-slate-900">{{ $arrival->port_of_loading ?: '-' }}</span>
                    </div>
	                    <div class="flex items-start gap-2 sm:col-span-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.containers') }}</span>
	                        <span class="text-slate-900">
	                            @if ($containerDetails->count())
	                                <div class="space-y-1">
	                                    @foreach ($containerDetails as $idx => $row)
	                                        <div class="text-sm">
	                                            {{ $idx + 1 }}. {{ $row['container_no'] }}
	                                        </div>
	                                    @endforeach
	                                </div>
	                            @else
	                                -
	                            @endif
	                        </span>
	                    </div>
	                    <div class="flex items-start gap-2">
	                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.currency') }}</span>
	                        <span class="text-slate-900">{{ $arrival->currency }}</span>
	                    </div>
	                </div>
                @if ($arrival->notes)
                    <div class="flex items-start gap-2 pt-2 border-t border-slate-200 text-sm">
                        <span class="font-semibold text-slate-700 min-w-[100px]">{{ __('incoming.arrivals.show.notes') }}</span>
                        <span class="text-slate-900">{{ $arrival->notes }}</span>
                    </div>
                @endif
            </div>

            <!-- Inspection Summary (read-only) -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">{{ __('incoming.arrivals.show.inspection_title') }}</h3>
                    <p class="text-sm text-slate-600 mt-1">{{ __('incoming.arrivals.show.inspection_desc') }}</p>
                </div>

                @php
                    $containers = $arrival->containers ?? collect();
                    $hasContainerInspection = $containers->contains(fn ($c) => (bool) $c->inspection);
                @endphp

                @if ($hasContainerInspection)
                    <div class="space-y-4">
                        @foreach ($containers as $container)
                            @php
                                $inspection = $container->inspection;
                                $statusColor = $inspection?->status === 'damage'
                                    ? 'bg-red-100 text-red-700'
                                    : ($inspection ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700');
                                $photos = [
                                    'Left' => $inspection?->photo_left,
                                    'Right' => $inspection?->photo_right,
                                    'Front' => $inspection?->photo_front,
                                    'Back' => $inspection?->photo_back,
                                    'Inside' => $inspection?->photo_inside,
                                    'Seal' => $inspection?->photo_seal,
                                ];
                                $issuesMap = [
                                    'Left' => $inspection?->issues_left ?? [],
                                    'Right' => $inspection?->issues_right ?? [],
                                    'Front' => $inspection?->issues_front ?? [],
                                    'Back' => $inspection?->issues_back ?? [],
                                ];
                                $containerNo = strtoupper(trim((string) ($container->container_no ?? '')));
                                $sealCode = strtoupper(trim((string) (($inspection?->seal_code) ?: ($container->seal_code ?? ''))));
                                $driverName = trim((string) ($inspection?->driver_name ?? ''));
                            @endphp

                            <div class="border border-slate-200 rounded-2xl p-4 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm font-semibold text-slate-900">
                                        Container: {{ $containerNo ?: '-' }}
                                        @if ($sealCode !== '')
                                            <span class="text-slate-500">• Seal {{ $sealCode }}</span>
                                        @endif
                                        @if ($driverName !== '')
                                            <span class="text-slate-500">• Driver {{ $driverName }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                                            {{ $inspection ? strtoupper($inspection->status) : 'PENDING' }}
                                        </span>
                                        @if ($inspection)
                                            <div class="text-xs text-slate-500">
                                                {{ __('incoming.arrivals.show.updated') }} {{ $inspection->updated_at?->format('Y-m-d H:i') ?? '-' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if ($inspection?->notes)
                                    <div class="text-sm text-slate-800">
                                        <span class="font-semibold text-slate-700">{{ __('incoming.arrivals.show.notes') }}</span> {{ $inspection->notes }}
                                    </div>
                                @endif

                                @if ($inspection)
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

                                    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
                                        @foreach ($photos as $label => $path)
                                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                                <div class="px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700">{{ $label }}</div>
                                                @if ($path)
                                                    <img src="{{ Storage::url($path) }}" alt="{{ $label }}" class="w-full h-36 object-cover">
                                                @else
                                                    <div class="h-36 flex items-center justify-center text-sm text-slate-400">{{ __('incoming.arrivals.show.no_photo') }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($arrival->inspection)
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
                            {{ __('incoming.arrivals.show.updated') }} {{ $arrival->inspection->updated_at?->format('Y-m-d H:i') ?? '-' }}
                        </div>
                    </div>

                    @if ($arrival->inspection->notes)
                        <div class="text-sm text-slate-800">
                            <span class="font-semibold text-slate-700">{{ __('incoming.arrivals.show.notes') }}</span> {{ $arrival->inspection->notes }}
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
                                    <div class="h-40 flex items-center justify-center text-sm text-slate-400">{{ __('incoming.arrivals.show.no_photo') }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-slate-600">
                        {{ __('incoming.arrivals.show.no_inspection') }}
                    </div>
                @endif
            </div>

            <!-- Items Table -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ __('incoming.arrivals.show.items_title') }}</h3>
                        <p class="text-sm text-slate-600 mt-1">{{ __('incoming.arrivals.show.items_desc') }}</p>
                    </div>
                    <a href="{{ route('departure-items.create', $arrival) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('incoming.arrivals.show.add_item') }}
                    </a>
                </div>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-max w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.part_name') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.size') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.qty_bundle') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.qty_goods') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.nett') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.gross') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.price_kg') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.total') }}</th>
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.received') }}</th>
                                <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">{{ __('incoming.arrivals.show.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
	                            @php
	                                $totalQtyBundle = (float) $arrival->items->sum(fn ($i) => (float) ($i->qty_bundle ?? 0));
	                                $totalQtyGoods = (float) $arrival->items->sum(fn ($i) => (float) ($i->qty_goods ?? 0));
	                                $totalNett = (float) $arrival->items->sum(fn ($i) => (float) ($i->weight_nett ?? 0));
	                                $totalGross = (float) $arrival->items->sum(fn ($i) => (float) ($i->weight_gross ?? 0));
	                                $totalPrice = (float) $arrival->items->sum(fn ($i) => (float) ($i->total_price ?? 0));
	                                $totalReceived = (float) $arrival->items->sum(fn ($i) => (float) $i->receives->sum('qty'));
	                            @endphp
	                            @foreach ($arrival->items as $item)
	                                @php
	                                    $receivedQty = $item->receives->sum('qty');
	                                    $remainingQty = max(0, ($item->qty_goods ?? 0) - $receivedQty);
	                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 text-slate-800 whitespace-nowrap">
                                        <div class="font-semibold">
                                            {{ $item->display_part_name }}
                                            @if ($item->is_foc)
                                                <span class="ml-1 inline-flex align-middle items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700 ring-1 ring-inset ring-sky-200">FOC</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700 font-mono text-xs whitespace-nowrap">{{ $item->size ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ $item->qty_bundle }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ $item->qty_goods }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ number_format($item->weight_nett, 0) }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ number_format($item->weight_gross, 0) }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">
                                        {{ number_format($item->price, 3) }}
                                        <span class="text-[10px] text-slate-500 ml-0.5">/{{ $item->unit_weight ?? 'KG' }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-800 font-semibold whitespace-nowrap">{{ number_format($item->total_price, 2) }}</td>
                                    <td class="px-4 py-4">
                                        <div class="text-slate-800 font-semibold">{{ number_format($receivedQty) }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->receives->count() }} {{ $item->receives->count() != 1 ? __('incoming.arrivals.show.receives_suffix') : __('incoming.arrivals.show.receive_suffix') }}</div>
                                    </td>
		                                    <td class="px-4 py-4">
		                                        <div class="flex justify-center">
		                                            <a href="{{ route('departure-items.edit', $item) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
		                                                {{ __('incoming.arrivals.show.edit_item') }}
		                                            </a>
		                                        </div>
		                                    </td>
	                                </tr>
	                            @endforeach
	                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr class="text-slate-800 font-semibold">
                                <td class="px-4 py-3 whitespace-nowrap">TOTAL</td>
                                <td class="px-4 py-3 text-slate-500 whitespace-nowrap">—</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($totalQtyBundle, 0) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($totalQtyGoods, 0) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($totalNett, 0) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($totalGross, 0) }}</td>
                                <td class="px-4 py-3 text-slate-500 whitespace-nowrap">—</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($totalPrice, 2) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($totalReceived, 0) }}</td>
                                <td class="px-4 py-3 text-center text-slate-500 whitespace-nowrap">—</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
	            </div>
        </div>
    </div>
</x-app-layout>
