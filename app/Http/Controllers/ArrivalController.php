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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DepartureDetailExport;
use App\Traits\LogsActivity;

class ArrivalController extends Controller
{
    use LogsActivity;

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
            ->map(fn($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        return $codes->isEmpty() ? null : $codes->implode("\n");
    }

    private function parseWidthFromSize(?string $size): ?float
    {
        $raw = trim((string) $size);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(',', '.', $raw);
        if (!preg_match_all('/\d+(?:\.\d+)?/', $raw, $matches)) {
            return null;
        }

        $numbers = collect($matches[0] ?? [])
            ->map(fn($n) => (float) $n)
            ->values()
            ->all();

        // We infer "width" using heuristics because inputs vary:
        // - Sheet: "0.25 x 640 x 1215" => width is typically the smaller of the 2 big numbers (640 vs 1215).
        // - Coil:  "0.7 x 530 x C"     => width is the only big number (530).
        // - Some users may swap order; we prefer choosing from "big" dimensions (>= 10).
        $big = array_values(array_filter($numbers, fn($n) => $n >= 10));
        if (count($big) >= 2) {
            return min($big);
        }
        if (count($big) === 1) {
            return $big[0];
        }

        // Fallback to "second number" for legacy patterns.
        if (count($numbers) >= 2) {
            return $numbers[1];
        }

        return null;
    }

    /**
     * Infer HS Codes from a list of items (either model or array).
     * Strictly uses Part Master HS Codes. If no Part Master code is found,
     * it returns null rather than guessing.
     */
    private function inferHsCodesFromItems(iterable $items): ?string
    {
        $codes = collect($items)
            ->map(function ($item) {
                // 1. Try to get from the Part model if possible
                $hsCodeModel = null;
                if ($item instanceof ArrivalItem) {
                    $hsCodeModel = $item->part?->hs_code;
                } elseif (is_array($item) && !empty($item['part_id'])) {
                    $hsCodeModel = Part::find($item['part_id'])?->hs_code;
                }

                return $hsCodeModel ? strtoupper(trim((string) $hsCodeModel)) : null;
            })
            ->filter()
            ->flatMap(fn($code) => collect(preg_split('/[\r\n,;]+/', (string) $code) ?: []))
            ->map(fn($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        return $codes->isEmpty() ? null : $codes->implode("\n");
    }

    private function syncHsCodes(Arrival $arrival): void
    {
        $arrival->loadMissing('items.part');
        $normalizedHsCodes = $this->inferHsCodesFromItems($arrival->items);

        $hsCodePrimary = null;
        if ($normalizedHsCodes) {
            $hsCodePrimary = collect(preg_split('/\r\n|\r|\n/', $normalizedHsCodes) ?: [])
                ->filter()
                ->first() ?: null;
        }

        $arrival->withoutEvents(function () use ($arrival, $normalizedHsCodes, $hsCodePrimary) {
            $arrival->update([
                'hs_codes' => $normalizedHsCodes,
                'hs_code' => $hsCodePrimary,
            ]);
        });
    }

    private function filterArrivalColumns(array $data): array
    {
        return collect($data)
            ->filter(fn($value, $key) => Schema::hasColumn('arrivals', (string) $key))
            ->all();
    }

    private function hasPendingReceives(Arrival $arrival): bool
    {
        $arrival->loadMissing(['items.receives', 'containers.inspection']);

        $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';

        // Require container inspections (when containers exist)
        if (!$isLocal && $arrival->containers && $arrival->containers->isNotEmpty()) {
            $hasMissingInspection = $arrival->containers->contains(fn($c) => !$c->inspection);
            if ($hasMissingInspection) {
                return true;
            }
        }

        // Require TAG filled for all receive rows (non-local only).
        if (!$isLocal) {
            $hasMissingTag = $arrival->items
                ->flatMap(fn($i) => $i->receives ?? collect())
                ->contains(fn($r) => !is_string($r->tag) || trim($r->tag) === '');
            if ($hasMissingTag) {
                return true;
            }
        }

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
        $departures = Arrival::with(['vendor', 'creator', 'items.receives', 'containers.inspection'])
            ->whereHas('vendor', fn($q) => $q->where('vendor_type', '!=', 'local'))
            ->latest()
            ->paginate(10);

        foreach ($departures as $arrival) {
            $arrival->receive_complete = !$this->hasPendingReceives($arrival);
        }

        return view('arrivals.index', ['departures' => $departures]);
    }

    public function create()
    {
        $vendors = Vendor::query()
            ->where('vendor_type', '!=', 'local')
            ->orderBy('vendor_name')
            ->get();
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
                $item['unit_goods'] = isset($item['unit_goods']) ? strtoupper(trim((string) $item['unit_goods'])) : null;
                $item['unit_bundle'] = isset($item['unit_bundle']) ? strtoupper(trim((string) $item['unit_bundle'])) : null;
                return $item;
            }, $items);
            $request->merge(['items' => $items]);
        }

        $request->merge([
            'invoice_no' => strtoupper(trim((string) $request->input('invoice_no', ''))),
        ]);

        $validated = $request->validate([
            'invoice_no' => ['required', 'string', 'max:255', Rule::unique('arrivals', 'invoice_no')],
            'invoice_date' => ['required', 'date'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vendor_name' => ['nullable', 'string'], // Allow vendor_name but not required
            'vessel' => ['nullable', 'string', 'max:255'],
            'trucking_company_id' => ['nullable', 'exists:trucking_companies,id'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'eta_gci' => ['nullable', 'date'],
            'bl_no' => ['nullable', 'string', 'max:255'],
            'bl_status' => ['nullable', 'in:surrender,draft'],
            'bl_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'price_term' => ['nullable', 'string', 'max:50'],
            'port_of_loading' => ['nullable', 'string', 'max:255'],
            'container_numbers' => ['nullable', 'string'],
            'seal_code' => ['nullable', 'string', 'max:100'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'hs_codes' => ['nullable', 'string'],
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
            'items.*.unit_bundle' => ['nullable', 'string', 'max:20', Rule::in(['BUNDLE', 'PALLET', 'BOX', 'BAG', 'ROLL'])],
            'items.*.qty_goods' => ['required', 'integer', 'min:1'],
            'items.*.unit_goods' => ['nullable', 'string', 'max:20', Rule::in(['PCS', 'COIL', 'SHEET', 'SET', 'EA', 'ROLL', 'KGM'])],
            'items.*.weight_nett' => ['required', 'numeric', 'min:0'],
            'items.*.unit_weight' => ['nullable', 'string', 'max:20'],
            'items.*.weight_gross' => ['required', 'numeric', 'min:0'],
            'items.*.total_amount' => ['required', 'numeric', 'min:0'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        foreach (($validated['items'] ?? []) as $index => $item) {
            $nett = (float) ($item['weight_nett'] ?? 0);
            $gross = (float) ($item['weight_gross'] ?? 0);
            if ($nett > $gross) {
                throw ValidationException::withMessages([
                    "items.{$index}.weight_nett" => 'Net weight harus lebih kecil atau sama dengan gross weight.',
                ]);
            }
        }

        $vendorId = $validated['vendor_id'];
        $validated['invoice_no'] = strtoupper(trim((string) ($validated['invoice_no'] ?? '')));

        $billOfLadingFilePath = null;
        if ($request->hasFile('bl_file')) {
            $billOfLadingFilePath = $request->file('bl_file')->storePublicly('bill_of_ladings', 'public');
        }

        $arrival = DB::transaction(function () use ($validated, $vendorId, $billOfLadingFilePath) {
            $normalizedContainers = collect($validated['containers'] ?? [])
                ->map(function ($row) {
                    $containerNo = strtoupper(trim((string) ($row['container_no'] ?? '')));
                    $sealCode = strtoupper(trim((string) ($row['seal_code'] ?? '')));
                    return [
                        'container_no' => $containerNo,
                        'seal_code' => $sealCode !== '' ? $sealCode : null,
                    ];
                })
                ->filter(fn($row) => $row['container_no'] !== '')
                ->values();

            if ($normalizedContainers->isEmpty() && !empty($validated['container_numbers'])) {
                $defaultSeal = isset($validated['seal_code']) ? strtoupper(trim((string) $validated['seal_code'])) : null;
                $lines = preg_split('/\r\n|\r|\n/', (string) $validated['container_numbers']) ?: [];
                $normalizedContainers = collect($lines)
                    ->map(function ($line) use ($defaultSeal) {
                        $raw = trim((string) $line);
                        if ($raw === '')
                            return null;
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
                'bill_of_lading_status' => $validated['bl_status'] ?? null,
                'bill_of_lading_file' => $billOfLadingFilePath,
                'price_term' => $validated['price_term'] ?? null,
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
                    'gci_part_id' => $part?->gci_part_id,
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

                        $weightCenti = $this->toCents($item['weight_nett'] ?? 0);
                        if ($weightCenti > 0) {
                            $priceMilli = intdiv(($totalCents * 1000) + intdiv($weightCenti, 2), $weightCenti);
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

            $this->syncHsCodes($arrival);

            return $arrival;
        });

        $this->logActivity('STORE Departure', "arrival_id:{$arrival->id} invoice:{$arrival->invoice_no}", [
            'vendor_id' => $validated['vendor_id'],
            'items_count' => count($validated['items']),
        ]);

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

        $logData = ['arrival_id' => $arrival->id, 'invoice_no' => $arrival->invoice_no, 'vendor_id' => $arrival->vendor_id];

        DB::transaction(function () use ($arrival) {
            foreach ($arrival->items as $item) {
                $item->receives()->delete();
            }
            $arrival->items()->delete();
            $arrival->delete();
        });

        $this->logActivity('DELETE Departure', "invoice:{$logData['invoice_no']}", $logData);

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

        $request->merge([
            'invoice_no' => strtoupper(trim((string) $request->input('invoice_no', ''))),
        ]);

        $data = $request->validate([
            'invoice_no' => ['required', 'string', 'max:255', Rule::unique('arrivals', 'invoice_no')->ignore($departure->id)],
            'invoice_date' => ['required', 'date'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'eta_gci' => ['nullable', 'date'],
            'vessel' => ['nullable', 'string', 'max:255'],
            'bl_no' => ['nullable', 'string', 'max:255'],
            'bl_status' => ['nullable', 'in:surrender,draft'],
            'bl_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'price_term' => ['nullable', 'string', 'max:50'],
            'port_of_loading' => ['nullable', 'string', 'max:255'],
            'container_numbers' => ['nullable', 'string'],
            'seal_code' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'hs_codes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['invoice_no'] = strtoupper(trim((string) ($data['invoice_no'] ?? '')));

        $billOfLadingFilePath = null;
        if ($request->hasFile('bl_file')) {
            $billOfLadingFilePath = $request->file('bl_file')->storePublicly('bill_of_ladings', 'public');
        }

        $departureData = [
            'invoice_no' => $data['invoice_no'],
            'invoice_date' => $data['invoice_date'],
            'ETD' => $data['etd'] ?? null,
            'ETA' => $data['eta'] ?? null,
            'ETA_GCI' => $data['eta_gci'] ?? null,
            'vessel' => $data['vessel'] ?? null,
            'bill_of_lading' => $data['bl_no'] ?? null,
            'bill_of_lading_status' => $data['bl_status'] ?? null,
            'price_term' => $data['price_term'] ?? null,
            'port_of_loading' => $data['port_of_loading'] ?? null,
            'container_numbers' => $data['container_numbers'] ?? null,
            'seal_code' => $data['seal_code'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'notes' => $data['notes'] ?? null,
        ];

        $oldBillFile = $departure->bill_of_lading_file;
        if ($billOfLadingFilePath !== null) {
            $departureData['bill_of_lading_file'] = $billOfLadingFilePath;
        }

        DB::transaction(function () use ($departure, $departureData, $data, $oldBillFile, $billOfLadingFilePath) {
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

            if ($billOfLadingFilePath !== null && $oldBillFile) {
                Storage::disk('public')->delete($oldBillFile);
            }

            if (Schema::hasTable('arrival_containers')) {
                $departure->containers()->delete();
                if ($normalizedContainers->isNotEmpty()) {
                    $departure->containers()->createMany($normalizedContainers->all());
                }
            }
        });

        $this->syncHsCodes($departure);

        $this->logActivity('UPDATE Departure', "arrival_id:{$departure->id} invoice:{$departure->invoice_no}");

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
                'margin-top' => 0,
                'margin-bottom' => 0,
                'margin-left' => 0,
                'margin-right' => 0,
                'enable-local-file-access' => true,
                'print-media-type' => true,
                'encoding' => 'UTF-8',
                // Prevent wkhtmltopdf from shrinking the whole page due to minor overflows.
                'disable-smart-shrinking' => true,
                // Slight bump so the output fills A4 better without spilling to a new page.
                'zoom' => 1.1,
            ]);

        return $pdf->inline($filename)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function exportDetail(Arrival $departure)
    {
        $filename = 'Departure-' . str_replace(['/', '\\'], '-', ($departure->invoice_no ?: $departure->arrival_no)) . '.xlsx';
        return Excel::download(new DepartureDetailExport($departure), $filename);
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

    public function createItem(Arrival $departure)
    {
        $arrival = $departure;
        $arrival->loadMissing(['vendor', 'items.receives']);

        if (!$this->hasPendingReceives($arrival)) {
            return redirect()
                ->route('departures.show', $arrival)
                ->with('error', 'Departure sudah complete receive, item tidak bisa ditambah.');
        }

        $parts = Part::query()
            ->where('vendor_id', $arrival->vendor_id)
            ->where('status', 'active')
            ->orderBy('part_no')
            ->get();

        $item = new ArrivalItem();

        return view('arrival-items.create', compact('arrival', 'parts', 'item'));
    }

    public function storeItem(Request $request, Arrival $departure)
    {
        $arrival = $departure;
        $arrival->loadMissing(['vendor', 'items.receives']);

        if (!$this->hasPendingReceives($arrival)) {
            return redirect()
                ->route('departures.show', $arrival)
                ->with('error', 'Departure sudah complete receive, item tidak bisa ditambah.');
        }

        $request->merge([
            'weight_nett' => $this->normalizeDecimalInput($request->input('weight_nett')),
            'weight_gross' => $this->normalizeDecimalInput($request->input('weight_gross')),
            'total_amount' => $this->normalizeDecimalInput($request->input('total_amount')),
            'unit_goods' => ($request->input('unit_goods') === null) ? null : strtoupper(trim((string) $request->input('unit_goods'))),
            'unit_bundle' => ($request->input('unit_bundle') === null) ? null : strtoupper(trim((string) $request->input('unit_bundle'))),
        ]);

        $data = $request->validate([
            'material_group' => ['nullable', 'string', 'max:255'],
            'part_id' => ['required', 'exists:parts,id'],
            'size' => ['nullable', 'string', 'max:100'],
            'unit_bundle' => ['nullable', 'string', 'max:20', Rule::in(['BUNDLE', 'PALLET', 'BOX', 'BAG', 'ROLL'])],
            'qty_bundle' => ['nullable', 'integer', 'min:0'],
            'qty_goods' => ['required', 'integer', 'min:1'],
            'unit_goods' => ['nullable', 'string', 'max:20', Rule::in(['PCS', 'COIL', 'SHEET', 'SET', 'EA', 'ROLL', 'KGM'])],
            'weight_nett' => ['required', 'numeric', 'min:0'],
            'weight_gross' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $part = Part::find($data['part_id']);
        if ($part && (int) $part->vendor_id !== (int) $arrival->vendor_id) {
            throw ValidationException::withMessages([
                'part_id' => "Part {$part->part_no} does not belong to the selected vendor.",
            ]);
        }

        $nett = (float) ($data['weight_nett'] ?? 0);
        $gross = (float) ($data['weight_gross'] ?? 0);
        if ($nett > $gross) {
            throw ValidationException::withMessages([
                'weight_nett' => 'Net weight harus lebih kecil atau sama dengan gross weight.',
            ]);
        }

        $normalizedNett = $this->normalizeDecimalInput($data['weight_nett']);
        $normalizedGross = $this->normalizeDecimalInput($data['weight_gross']);
        $normalizedTotal = $this->normalizeDecimalInput($data['total_amount']);

        $qtyGoods = (int) $data['qty_goods'];
        $totalPrice = round((float) $normalizedTotal, 2);
        $totalCents = $this->toCents($normalizedTotal);

        $weightCenti = $this->toCents($normalizedNett);
        if ($weightCenti > 0) {
            $priceMilli = intdiv(($totalCents * 1000) + intdiv($weightCenti, 2), $weightCenti);
        } else {
            $priceMilli = $qtyGoods > 0 ? intdiv($totalCents * 10, $qtyGoods) : 0;
        }
        $price = $this->formatMilli($priceMilli);

        $arrival->items()->create([
            'part_id' => $data['part_id'],
            'gci_part_id' => $part?->gci_part_id,
            'material_group' => $data['material_group'] ?? null,
            'size' => $data['size'] ?? null,
            'unit_bundle' => $data['unit_bundle'] ?? null,
            'qty_bundle' => (int) ($data['qty_bundle'] ?? 0),
            'qty_goods' => $qtyGoods,
            'unit_goods' => $data['unit_goods'] ?? null,
            'weight_nett' => $normalizedNett,
            'unit_weight' => 'KGM',
            'weight_gross' => $normalizedGross,
            'price' => $price,
            'total_price' => $totalPrice,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncHsCodes($arrival);

        $this->logActivity('STORE Departure Item', "arrival_id:{$arrival->id} part_id:{$data['part_id']}", [
            'qty_goods' => $qtyGoods,
        ]);

        return redirect()
            ->route('departures.show', $arrival)
            ->with('success', 'Item berhasil ditambahkan.');
    }

    public function updateItem(Request $request, ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['arrival', 'receives']);

        $request->merge([
            'weight_nett' => $this->normalizeDecimalInput($request->input('weight_nett')),
            'weight_gross' => $this->normalizeDecimalInput($request->input('weight_gross')),
            'total_amount' => $this->normalizeDecimalInput($request->input('total_amount')),
            'unit_goods' => ($request->input('unit_goods') === null) ? null : strtoupper(trim((string) $request->input('unit_goods'))),
            'unit_bundle' => ($request->input('unit_bundle') === null) ? null : strtoupper(trim((string) $request->input('unit_bundle'))),
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
            'unit_bundle' => ['nullable', 'string', 'max:20', Rule::in(['BUNDLE', 'PALLET', 'BOX', 'BAG', 'ROLL'])],
            'qty_bundle' => ['nullable', 'integer', 'min:0'],
            'qty_goods' => ['required', 'integer', 'min:1'],
            'unit_goods' => ['nullable', 'string', 'max:20', Rule::in(['PCS', 'COIL', 'SHEET', 'SET', 'EA', 'ROLL', 'KGM'])],
            'weight_nett' => ['required', 'numeric', 'min:0'],
            'weight_gross' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $normalizedNett = $this->normalizeDecimalInput($data['weight_nett']);
        $normalizedGross = $this->normalizeDecimalInput($data['weight_gross']);
        $normalizedTotal = $this->normalizeDecimalInput($data['total_amount']);

        if ((float) $normalizedNett > (float) $normalizedGross) {
            throw ValidationException::withMessages([
                'weight_nett' => 'Net weight harus lebih kecil atau sama dengan gross weight.',
            ]);
        }

        $qtyGoods = (int) $data['qty_goods'];
        $totalPrice = round((float) $normalizedTotal, 2);
        $totalCents = $this->toCents($normalizedTotal);

        $goodsUnit = strtoupper(trim((string) ($data['unit_goods'] ?? '')));
        $weightCenti = $this->toCents($normalizedNett);
        if ($weightCenti > 0) {
            $priceMilli = intdiv(($totalCents * 1000) + intdiv($weightCenti, 2), $weightCenti);
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

        $this->syncHsCodes($arrivalItem->arrival);

        $this->logActivity('UPDATE Departure Item', "item_id:{$arrivalItem->id}", [
            'qty_goods' => $qtyGoods,
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

            // Normalize EXIF orientation (many phone photos are stored rotated with EXIF metadata).
            // Dompdf often ignores EXIF orientation, which can cause portrait photos to appear sideways.
            $didNormalize = false;
            if (function_exists('exif_read_data') && function_exists('imagecreatefromstring') && function_exists('imagerotate')) {
                $orientation = null;
                if ($absolutePath && is_file($absolutePath)) {
                    $exif = @exif_read_data($absolutePath);
                    if (is_array($exif) && isset($exif['Orientation'])) {
                        $orientation = (int) $exif['Orientation'];
                    }
                } else {
                    $tmp = @tempnam(sys_get_temp_dir(), 'exif_');
                    if ($tmp) {
                        @file_put_contents($tmp, $bytes);
                        $exif = @exif_read_data($tmp);
                        @unlink($tmp);
                        if (is_array($exif) && isset($exif['Orientation'])) {
                            $orientation = (int) $exif['Orientation'];
                        }
                    }
                }

                $angle = null;
                if ($orientation === 3) {
                    $angle = 180;
                } elseif ($orientation === 6) {
                    $angle = -90;
                } elseif ($orientation === 8) {
                    $angle = 90;
                }

                if ($angle !== null) {
                    $img = @imagecreatefromstring($bytes);
                    if ($img !== false) {
                        $rotated = @imagerotate($img, $angle, 0);
                        @imagedestroy($img);
                        if ($rotated !== false) {
                            ob_start();
                            imagejpeg($rotated, null, 92);
                            $bytes = (string) ob_get_clean();
                            @imagedestroy($rotated);
                            $mime = 'image/jpeg';
                            $didNormalize = true;
                        }
                    }
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
                'src' => (!$didNormalize && $absolutePath && is_file($absolutePath))
                    ? ('file://' . $absolutePath)
                    : ('data:' . $mime . ';base64,' . base64_encode($bytes)),
                'class' => $class,
            ];
        };

        $containersWithInspection = $arrival->containers
            ? $arrival->containers->filter(fn($c) => (bool) $c->inspection)->values()
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
                    // Optional damage-detail photos
                    'damage1' => $photoMeta($inspection?->photo_damage_1 ?: $inspection?->photo_damage),
                    'damage2' => $photoMeta($inspection?->photo_damage_2),
                    'damage3' => $photoMeta($inspection?->photo_damage_3),
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
