<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\ArrivalContainer;
use App\Models\ArrivalItem;
use App\Models\Part;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArrivalController extends Controller
{
    private function toCents(mixed $value): int
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        $raw = trim($value);
        if ($raw === '') {
            return 0;
        }

        $raw = str_replace(',', '.', $raw);

        $negative = false;
        if (str_starts_with($raw, '-')) {
            $negative = true;
            $raw = substr($raw, 1);
        }

        $parts = explode('.', $raw, 2);
        $whole = preg_replace('/\\D+/', '', $parts[0] ?? '') ?: '0';
        $frac = preg_replace('/\\D+/', '', $parts[1] ?? '');
        $frac = substr(str_pad($frac, 2, '0'), 0, 2);

        $cents = ((int) $whole) * 100 + (int) $frac;
        return $negative ? -$cents : $cents;
    }

    private function formatMilli(int $milli): string
    {
        $negative = $milli < 0;
        $milli = abs($milli);
        $s = (string) $milli;
        if (strlen($s) <= 3) {
            $s = str_pad($s, 4, '0', STR_PAD_LEFT);
        }
        $int = substr($s, 0, -3);
        $frac = substr($s, -3);
        $intPart = ltrim($int, '0');
        if ($intPart === '') {
            $intPart = '0';
        }
        return ($negative ? '-' : '') . $intPart . '.' . $frac;
    }
    private function normalizeDecimalInput(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        return str_replace(',', '.', $trimmed);
    }

    private function normalizeHsCodes(?string $raw): ?string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $parts = preg_split('/[\r\n,;]+/', $value) ?: [];
        $codes = collect($parts)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        return $codes->isEmpty() ? null : $codes->implode("\n");
    }

    private function filterArrivalColumns(array $data): array
    {
        return collect($data)
            ->filter(fn ($value, $key) => Schema::hasColumn('arrivals', (string) $key))
            ->all();
    }

    private function hasPendingReceives(Arrival $arrival): bool
    {
        $arrival->loadMissing('items.receives');

        foreach ($arrival->items as $item) {
            $received = $item->receives->sum('qty');
            if (($item->qty_goods - $received) > 0) {
                return true;
            }
        }

        return false;
    }

    public function index()
    {
        $departures = Arrival::with(['vendor', 'creator', 'items.receives'])
            ->latest()
            ->paginate(10);

        return view('arrivals.index', ['departures' => $departures]);
    }

    public function create()
    {
        $vendors = Vendor::orderBy('vendor_name')->get();
        $parts = Part::with('vendor')->where('status', 'active')->get();
        $truckings = \App\Models\Trucking::where('status', 'active')->orderBy('company_name')->get();

        return view('arrivals.create', compact('vendors', 'parts', 'truckings'));
    }

    public function store(Request $request)
    {
        $items = $request->input('items');
        if (is_array($items)) {
            $items = array_map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }
                $item['weight_nett'] = $this->normalizeDecimalInput($item['weight_nett'] ?? null);
                $item['weight_gross'] = $this->normalizeDecimalInput($item['weight_gross'] ?? null);
                $item['total_amount'] = $this->normalizeDecimalInput($item['total_amount'] ?? null);
                $item['price'] = $this->normalizeDecimalInput($item['price'] ?? null);
                return $item;
            }, $items);
            $request->merge(['items' => $items]);
        }

	        $validated = $request->validate([
	            'invoice_no' => ['required', 'string', 'max:255'],
	            'invoice_date' => ['required', 'date'],
	            'vendor_id' => ['required', 'exists:vendors,id'],
	            'vendor_name' => ['nullable', 'string'], // Allow vendor_name but not required
	            'vessel' => ['nullable', 'string', 'max:255'],
		            'trucking_company_id' => ['nullable', 'exists:trucking_companies,id'],
		            'etd' => ['nullable', 'date'],
		            'eta' => ['nullable', 'date'],
		            'eta_gci' => ['nullable', 'date'],
		            'bl_no' => ['nullable', 'string', 'max:255'],
	            'price_term' => ['nullable', 'string', 'max:50'],
	            'hs_code' => ['nullable', 'string', 'max:255'],
	            'hs_codes' => ['nullable', 'string', 'max:2000'],
	            'port_of_loading' => ['nullable', 'string', 'max:255'],
	            'container_numbers' => ['nullable', 'string'],
	            'seal_code' => ['nullable', 'string', 'max:100'],
	            'containers' => ['nullable', 'array'],
            'containers.*.container_no' => ['required_with:containers', 'string', 'max:50', 'distinct'],
            'containers.*.seal_code' => ['required_with:containers.*.container_no', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_group' => ['nullable', 'string', 'max:255'],
            'items.*.part_id' => ['required', 'exists:parts,id'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.qty_bundle' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_bundle' => ['nullable', 'string', 'max:20'],
            'items.*.qty_goods' => ['required', 'integer', 'min:1'],
            'items.*.unit_goods' => ['nullable', 'string', 'max:20'],
            'items.*.weight_nett' => ['required', 'numeric', 'min:0'],
            'items.*.unit_weight' => ['nullable', 'string', 'max:20'],
            'items.*.weight_gross' => ['required', 'numeric', 'min:0'],
            'items.*.total_amount' => ['required', 'numeric', 'min:0'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $vendorId = $validated['vendor_id'];
        $validated['invoice_no'] = strtoupper(trim((string) ($validated['invoice_no'] ?? '')));

        $arrival = DB::transaction(function () use ($validated, $vendorId) {
            $normalizedContainers = collect($validated['containers'] ?? [])
                ->map(function ($row) {
                    $containerNo = strtoupper(trim((string) ($row['container_no'] ?? '')));
                    $sealCode = strtoupper(trim((string) ($row['seal_code'] ?? '')));
                    return [
                        'container_no' => $containerNo,
                        'seal_code' => $sealCode !== '' ? $sealCode : null,
                    ];
                })
                ->filter(fn ($row) => $row['container_no'] !== '')
                ->values();

            if ($normalizedContainers->isEmpty() && !empty($validated['container_numbers'])) {
                $defaultSeal = isset($validated['seal_code']) ? strtoupper(trim((string) $validated['seal_code'])) : null;
                $lines = preg_split('/\r\n|\r|\n/', (string) $validated['container_numbers']) ?: [];
                $normalizedContainers = collect($lines)
                    ->map(function ($line) use ($defaultSeal) {
                        $raw = trim((string) $line);
                        if ($raw === '') return null;
                        $parts = preg_split('/\s+/', $raw) ?: [];
                        $containerNo = strtoupper(trim((string) ($parts[0] ?? '')));
                        $sealCode = strtoupper(trim((string) ($parts[1] ?? $defaultSeal ?? '')));
                        return [
                            'container_no' => $containerNo,
                            'seal_code' => $sealCode !== '' ? $sealCode : null,
                        ];
                    })
                    ->filter()
                    ->values();
            }

            $containerNumbersLegacy = $normalizedContainers->isNotEmpty()
                ? $normalizedContainers->pluck('container_no')->implode("\n")
                : ($validated['container_numbers'] ?? null);

	            $normalizedHsCodes = $this->normalizeHsCodes($validated['hs_codes'] ?? $validated['hs_code'] ?? null);
	            $hsCodePrimary = $normalizedHsCodes
	                ? (collect(preg_split('/\r\n|\r|\n/', $normalizedHsCodes) ?: [])->filter()->first() ?: null)
	                : null;

	            $arrivalData = [
	                'invoice_no' => $validated['invoice_no'],
	                'invoice_date' => $validated['invoice_date'],
	                'vendor_id' => $vendorId,
	                'vessel' => $validated['vessel'] ?? null,
	                'trucking_company_id' => $validated['trucking_company_id'] ?? null,
	                'ETD' => $validated['etd'] ?? null,
	                'ETA' => $validated['eta'] ?? null,
	                'ETA_GCI' => $validated['eta_gci'] ?? null,
	                'bill_of_lading' => $validated['bl_no'] ?? null,
	                'price_term' => $validated['price_term'] ?? null,
	                'hs_code' => $hsCodePrimary,
	                'hs_codes' => $normalizedHsCodes,
                'port_of_loading' => $validated['port_of_loading'] ?? null,
                'country' => $validated['port_of_loading'] ?? 'SOUTH KOREA',
                'container_numbers' => $containerNumbersLegacy,
                'seal_code' => $validated['seal_code'] ?? null,
                'currency' => $validated['currency'] ?? 'USD',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ];

            $arrival = Arrival::create($this->filterArrivalColumns($arrivalData));

            if ($normalizedContainers->isNotEmpty() && Schema::hasTable('arrival_containers')) {
                $arrival->containers()->createMany($normalizedContainers->all());
            }

            foreach ($validated['items'] as $index => $item) {
                // Skip items with qty_goods = 0
                if ($item['qty_goods'] <= 0) {
                    continue;
                }

                $part = Part::find($item['part_id']);
                if ($part && $part->vendor_id != $vendorId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.part_id" => "Part {$part->part_no} does not belong to the selected vendor.",
                    ]);
                }

                $arrival->items()->create([
                    'part_id' => $item['part_id'],
                    'material_group' => $item['material_group'] ?? null,
                    'size' => $item['size'] ?? null,
                    'qty_bundle' => $item['qty_bundle'] ?? 0,
                    'unit_bundle' => $item['unit_bundle'] ?? null,
                    'qty_goods' => $item['qty_goods'],
                    'unit_goods' => $item['unit_goods'] ?? null,
                    'weight_nett' => $item['weight_nett'],
                    'unit_weight' => $item['unit_weight'] ?? null,
                    'weight_gross' => $item['weight_gross'],
                    'price' => (function () use ($item) {
                        $qty = (int) ($item['qty_goods'] ?? 0);
                        $totalCents = $this->toCents($item['total_amount'] ?? 0);
                        $goodsUnit = strtoupper(trim((string) ($item['unit_goods'] ?? '')));

                        if (in_array($goodsUnit, ['KGM', 'KG'], true)) {
                            $weightCenti = $this->toCents($item['weight_nett'] ?? 0);
                            if ($weightCenti <= 0) {
                                return '0.000';
                            }
                            $priceMilli = intdiv($totalCents * 1000, $weightCenti);
                            return $this->formatMilli($priceMilli);
                        }

                        if ($qty <= 0) {
                            return '0.000';
                        }

                        $priceMilli = intdiv($totalCents * 10, $qty);
                        return $this->formatMilli($priceMilli);
                    })(),
                    'total_price' => round((float) $item['total_amount'], 2),
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $arrival;
        });

        return redirect()
            ->route('departures.index')
            ->with('success', 'Departure created successfully.');
    }

    public function show(Arrival $departure)
    {
        // Keep using $arrival internally for existing views/logic
        $arrival = $departure;
        $arrival->load(['vendor', 'creator', 'trucking', 'inspection', 'containers.inspection', 'items.part.vendor', 'items.receives']);

        $isReceiveComplete = !$this->hasPendingReceives($arrival);

        return view('arrivals.show', compact('arrival', 'isReceiveComplete'));
    }

    public function destroy(Arrival $departure)
    {
        $arrival = $departure;

        DB::transaction(function () use ($arrival) {
            foreach ($arrival->items as $item) {
                $item->receives()->delete();
            }
            $arrival->items()->delete();
            $arrival->delete();
        });

        return redirect()->route('departures.index')->with('success', 'Departure berhasil dihapus.');
    }

    public function edit(Arrival $departure)
    {
        if (!$this->hasPendingReceives($departure)) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Departure sudah complete receive, tidak bisa di-edit.');
        }

        $departure->load('containers');

        return view('arrivals.edit', ['arrival' => $departure]);
    }

    public function update(Request $request, Arrival $departure)
    {
        if (!$this->hasPendingReceives($departure)) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Departure sudah complete receive, tidak bisa di-edit.');
        }

	        $data = $request->validate([
	            'invoice_no' => ['required', 'string', 'max:255'],
	            'invoice_date' => ['required', 'date'],
	            'etd' => ['nullable', 'date'],
	            'eta' => ['nullable', 'date'],
	            'eta_gci' => ['nullable', 'date'],
	            'vessel' => ['nullable', 'string', 'max:255'],
	            'bl_no' => ['nullable', 'string', 'max:255'],
            'price_term' => ['nullable', 'string', 'max:50'],
            'hs_code' => ['nullable', 'string', 'max:255'],
            'hs_codes' => ['nullable', 'string', 'max:2000'],
            'port_of_loading' => ['nullable', 'string', 'max:255'],
            'container_numbers' => ['nullable', 'string'],
            'seal_code' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['invoice_no'] = strtoupper(trim((string) ($data['invoice_no'] ?? '')));

        $normalizedHsCodes = $this->normalizeHsCodes($data['hs_codes'] ?? $data['hs_code'] ?? null);
        $hsCodePrimary = $normalizedHsCodes
            ? (collect(preg_split('/\r\n|\r|\n/', $normalizedHsCodes) ?: [])->filter()->first() ?: null)
            : null;

	        $departureData = [
	            'invoice_no' => $data['invoice_no'],
	            'invoice_date' => $data['invoice_date'],
	            'ETD' => $data['etd'] ?? null,
	            'ETA' => $data['eta'] ?? null,
	            'ETA_GCI' => $data['eta_gci'] ?? null,
	            'vessel' => $data['vessel'] ?? null,
	            'bill_of_lading' => $data['bl_no'] ?? null,
	            'price_term' => $data['price_term'] ?? null,
	            'hs_code' => $hsCodePrimary,
            'hs_codes' => $normalizedHsCodes,
            'port_of_loading' => $data['port_of_loading'] ?? null,
            'container_numbers' => $data['container_numbers'] ?? null,
            'seal_code' => $data['seal_code'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'notes' => $data['notes'] ?? null,
        ];

        DB::transaction(function () use ($departure, $departureData, $data) {
            $defaultSeal = strtoupper(trim((string) ($data['seal_code'] ?? '')));
            $lines = preg_split('/\r\n|\r|\n/', (string) ($data['container_numbers'] ?? '')) ?: [];
            $normalizedContainers = collect($lines)
                ->map(function ($line) use ($defaultSeal) {
                    $raw = trim((string) $line);
                    if ($raw === '') {
                        return null;
                    }
                    $parts = preg_split('/\s+/', $raw) ?: [];
                    $containerNo = strtoupper(trim((string) ($parts[0] ?? '')));
                    if ($containerNo === '') {
                        return null;
                    }
                    $sealCode = strtoupper(trim((string) ($parts[1] ?? $defaultSeal)));
                    return [
                        'container_no' => $containerNo,
                        'seal_code' => $sealCode !== '' ? $sealCode : null,
                    ];
                })
                ->filter()
                ->unique('container_no')
                ->values();

            $containerNumbersLegacy = $normalizedContainers->isNotEmpty()
                ? $normalizedContainers->pluck('container_no')->implode("\n")
                : trim((string) ($data['container_numbers'] ?? ''));

            $departureData['container_numbers'] = $containerNumbersLegacy !== '' ? $containerNumbersLegacy : null;

            $departure->update($this->filterArrivalColumns($departureData));

            if (Schema::hasTable('arrival_containers')) {
                $departure->containers()->delete();
                if ($normalizedContainers->isNotEmpty()) {
                    $departure->containers()->createMany($normalizedContainers->all());
                }
            }
        });

        return redirect()->route('departures.show', $departure)->with('success', 'Departure berhasil di-update.');
    }

    public function printInvoice(Arrival $departure)
    {
        // Keep using $arrival internally for existing view/logic
        $arrival = $departure;
        $arrival->load(['vendor', 'trucking', 'containers', 'items.part']);

        // Clean filename - remove / and \ characters
        $filename = 'Commercial-Invoice-' . str_replace(['/', '\\'], '-', $arrival->invoice_no) . '.pdf';

        $wkhtmltopdfBinary = (string) config('snappy.pdf.binary', '');
        $canUseSnappy = $wkhtmltopdfBinary !== '' && is_file($wkhtmltopdfBinary) && is_executable($wkhtmltopdfBinary);

        if (!$canUseSnappy) {
            throw new \RuntimeException(
                "wkhtmltopdf binary not found/executable at `{$wkhtmltopdfBinary}`. " .
                "Install wkhtmltopdf or set WKHTML_PDF_BINARY in .env to the correct path."
            );
        }

        $pdf = SnappyPdf::loadView('arrivals.invoice', compact('arrival'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'margin-top' => 12,
                'margin-bottom' => 12,
                'margin-left' => 12,
                'margin-right' => 12,
                'enable-local-file-access' => true,
                'print-media-type' => true,
                'encoding' => 'UTF-8',
                'zoom' => 1.0,
            ]);

        return $pdf->inline($filename)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function editItem(ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['arrival', 'part', 'receives']);

        if (!$this->hasPendingReceives($arrivalItem->arrival)) {
            return redirect()
                ->route('departures.show', $arrivalItem->arrival)
                ->with('error', 'Departure sudah complete receive, item tidak bisa di-edit.');
        }

        if ($arrivalItem->receives()->exists()) {
            return redirect()
                ->route('departures.show', $arrivalItem->arrival)
                ->with('error', 'Item sudah punya receive, tidak bisa di-edit.');
        }

        return view('arrival-items.edit', ['item' => $arrivalItem, 'arrival' => $arrivalItem->arrival]);
    }

    public function updateItem(Request $request, ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['arrival', 'receives']);

        $request->merge([
            'weight_nett' => $this->normalizeDecimalInput($request->input('weight_nett')),
            'weight_gross' => $this->normalizeDecimalInput($request->input('weight_gross')),
            'total_amount' => $this->normalizeDecimalInput($request->input('total_amount')),
        ]);

        if (!$this->hasPendingReceives($arrivalItem->arrival)) {
            return redirect()
                ->route('departures.show', $arrivalItem->arrival)
                ->with('error', 'Departure sudah complete receive, item tidak bisa di-edit.');
        }

        if ($arrivalItem->receives()->exists()) {
            return redirect()
                ->route('departures.show', $arrivalItem->arrival)
                ->with('error', 'Item sudah punya receive, tidak bisa di-edit.');
        }

        $data = $request->validate([
            'material_group' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'unit_bundle' => ['nullable', 'string', 'max:20'],
            'qty_bundle' => ['nullable', 'integer', 'min:0'],
            'qty_goods' => ['required', 'integer', 'min:1'],
            'unit_goods' => ['nullable', 'string', 'max:20'],
            'weight_nett' => ['required', 'numeric', 'min:0'],
            'weight_gross' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $normalizedNett = $this->normalizeDecimalInput($data['weight_nett']);
        $normalizedGross = $this->normalizeDecimalInput($data['weight_gross']);
        $normalizedTotal = $this->normalizeDecimalInput($data['total_amount']);

        $qtyGoods = (int) $data['qty_goods'];
        $totalPrice = round((float) $normalizedTotal, 2);
        $totalCents = $this->toCents($normalizedTotal);

        $goodsUnit = strtoupper(trim((string) ($data['unit_goods'] ?? '')));
        if (in_array($goodsUnit, ['KGM', 'KG'], true)) {
            $weightCenti = $this->toCents($normalizedNett);
            $priceMilli = $weightCenti > 0 ? intdiv($totalCents * 1000, $weightCenti) : 0;
        } else {
            $priceMilli = $qtyGoods > 0 ? intdiv($totalCents * 10, $qtyGoods) : 0;
        }

        $price = $this->formatMilli($priceMilli);

        $arrivalItem->update([
            'material_group' => $data['material_group'] ?? null,
            'size' => $data['size'] ?? null,
            'unit_bundle' => $data['unit_bundle'] ?? null,
            'qty_bundle' => (int) ($data['qty_bundle'] ?? 0),
            'qty_goods' => $qtyGoods,
            'unit_goods' => $data['unit_goods'] ?? null,
            'weight_nett' => $normalizedNett,
            'unit_weight' => 'KGM',
            'weight_gross' => $normalizedGross,
            'total_price' => $totalPrice,
            'price' => $price,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('departures.show', $arrivalItem->arrival)
            ->with('success', 'Item berhasil di-update.');
    }

    public function printInspectionReport(Arrival $departure)
    {
        $arrival = $departure;
        $arrival->load(['vendor', 'containers.inspection.inspector', 'inspection.inspector']);

        $photoMeta = function (?string $publicPath): ?array {
            if (!$publicPath) {
                return null;
            }
            if (!Storage::disk('public')->exists($publicPath)) {
                return null;
            }

            $mime = Storage::disk('public')->mimeType($publicPath) ?: 'image/jpeg';
            $bytes = Storage::disk('public')->get($publicPath);

            $absolutePath = null;
            try {
                $absolutePath = Storage::disk('public')->path($publicPath);
            } catch (\Throwable) {
                $absolutePath = null;
            }

            if (!$absolutePath) {
                $guess = storage_path('app/public/' . ltrim($publicPath, '/'));
                if (is_file($guess)) {
                    $absolutePath = $guess;
                }
            }

            $class = 'is-landscape';
            if ($absolutePath) {
                $size = @getimagesize($absolutePath);
                if (is_array($size) && isset($size[0], $size[1])) {
                    $w = (int) $size[0];
                    $h = (int) $size[1];
                    $class = $h > $w ? 'is-portrait' : 'is-landscape';
                }
            } else {
                $size = @getimagesizefromstring($bytes);
                if (is_array($size) && isset($size[0], $size[1])) {
                    $w = (int) $size[0];
                    $h = (int) $size[1];
                    $class = $h > $w ? 'is-portrait' : 'is-landscape';
                }
            }

            return [
                'src' => $absolutePath && is_file($absolutePath) ? ('file://' . $absolutePath) : ('data:' . $mime . ';base64,' . base64_encode($bytes)),
                'class' => $class,
            ];
        };

        $containersWithInspection = $arrival->containers
            ? $arrival->containers->filter(fn ($c) => (bool) $c->inspection)->values()
            : collect();

        if ($containersWithInspection->isNotEmpty()) {
            $photosByContainerId = [];
            foreach ($containersWithInspection as $container) {
                $inspection = $container->inspection;
                $photosByContainerId[$container->id] = [
                    'left' => $photoMeta($inspection?->photo_left),
                    'right' => $photoMeta($inspection?->photo_right),
                    'front' => $photoMeta($inspection?->photo_front),
                    'back' => $photoMeta($inspection?->photo_back),
                    'inside' => $photoMeta($inspection?->photo_inside),
                    'seal' => $photoMeta($inspection?->photo_seal),
                    // Optional damage-detail photos (only photo_damage exists today)
                    'damage1' => $photoMeta($inspection?->photo_damage),
                    'damage2' => null,
                    'damage3' => null,
                ];
            }

            $pdf = Pdf::loadView('arrivals.container_inspection_report', [
                'arrival' => $arrival,
                'containers' => $containersWithInspection,
                'photosByContainerId' => $photosByContainerId,
            ])
                ->setPaper('a4', 'landscape')
                ->setWarnings(false);
        } elseif ($arrival->inspection) {
            $inspection = $arrival->inspection;
            $photos = [
                'left' => $photoMeta($inspection->photo_left),
                'right' => $photoMeta($inspection->photo_right),
                'front' => $photoMeta($inspection->photo_front),
                'back' => $photoMeta($inspection->photo_back),
                'inside' => $photoMeta($inspection->photo_inside),
            ];

            $pdf = Pdf::loadView('arrivals.inspection_report', compact('arrival', 'inspection', 'photos'))
                ->setPaper('a4', 'landscape')
                ->setWarnings(false);
        } else {
            abort(404);
        }

        $filename = 'Inspection-' . str_replace(['/', '\\'], '-', $arrival->invoice_no) . '.pdf';

        return $pdf->stream($filename);
    }
}
