<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

use App\Models\Receive;
use App\Models\ArrivalItem;
use App\Models\Arrival;
use App\Models\Inventory;
use App\Models\LocationInventory;
use App\Models\WarehouseLocation;
use App\Exports\CompletedInvoiceReceivesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Support\QrSvg;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ReceiveController extends Controller
{
    private function normalizeTag(?string $tag): ?string
    {
        $tag = is_string($tag) ? strtoupper(trim($tag)) : null;
        return ($tag === null || $tag === '') ? null : $tag;
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

    private function ensureTagsUniqueForArrivalItem(ArrivalItem $arrivalItem, array $tags, string $errorKey = 'tags', ?int $ignoreReceiveId = null): void
    {
        $incomingTags = collect($tags)
            ->pluck('tag')
            ->filter(fn($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn($tag) => strtoupper(trim($tag)))
            ->values();

        if ($incomingTags->isEmpty()) {
            return;
        }

        $duplicatesInRequest = $incomingTags
            ->countBy()
            ->filter(fn($count) => $count > 1)
            ->keys()
            ->values();

        if ($duplicatesInRequest->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                $errorKey => 'Ada TAG duplikat di input: ' . $duplicatesInRequest->implode(', '),
            ]));
        }

        $existingTags = $arrivalItem->receives()
            ->whereIn('tag', $incomingTags->all())
            ->when($ignoreReceiveId, fn($q) => $q->where('id', '!=', $ignoreReceiveId))
            ->pluck('tag')
            ->map(fn($tag) => strtoupper(trim((string) $tag)))
            ->unique()
            ->values();

        if ($existingTags->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                $errorKey => 'TAG sudah pernah diinput untuk item ini: ' . $existingTags->implode(', '),
            ]));
        }
    }

    // Note:
    // TAG fisik boleh sama antar item yang berbeda dalam invoice yang sama.
    // Yang wajib unik hanyalah TAG dalam scope 1 item (arrival_item_id).

    public function index()
    {
        // Group pending by invoice/departure
        $pendingArrivals = Arrival::with(['vendor', 'items.receives'])
            ->get()
            ->map(function ($arrival) {
                $remaining = $arrival->items->sum(function ($item) {
                    $received = $item->receives->sum('qty');
                    return max(0, $item->qty_goods - $received);
                });
                $arrival->remaining_qty = $remaining;
                $arrival->pending_items_count = $arrival->items->filter(function ($item) {
                    $received = $item->receives->sum('qty');
                    return ($item->qty_goods - $received) > 0;
                })->count();
                return $arrival;
            })
            ->filter(fn($arrival) => $arrival->remaining_qty > 0)
            ->values();

        return view('receives.index', [
            'pendingArrivals' => $pendingArrivals,
        ]);
    }

    public function completed()
    {
        // Show completed receives grouped by invoice/departure
        $arrivals = Arrival::query()
            ->select([
                'arrivals.id',
                'arrivals.arrival_no',
                'arrivals.invoice_no',
                'arrivals.invoice_date',
                'arrivals.vendor_id',
                // Tags count: unique TAG across items within the same invoice
                DB::raw("COUNT(DISTINCT NULLIF(TRIM(receives.tag), '')) as receives_count"),
                DB::raw('SUM(receives.qty) as total_qty'),
                DB::raw("SUM(CASE WHEN receives.qc_status = 'pass' THEN 1 ELSE 0 END) as pass_count"),
                DB::raw("SUM(CASE WHEN receives.qc_status IN ('reject','fail') THEN 1 ELSE 0 END) as fail_count"),
            ])
            ->join('arrival_items', 'arrival_items.arrival_id', '=', 'arrivals.id')
            ->join('receives', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->with('vendor')
            ->groupBy('arrivals.id', 'arrivals.arrival_no', 'arrivals.invoice_no', 'arrivals.invoice_date', 'arrivals.vendor_id')
            ->orderByDesc('arrivals.created_at')
            ->paginate(10);

        $statusCounts = Receive::select('qc_status', DB::raw('count(*) as total'))
            ->groupBy('qc_status')
            ->pluck('total', 'qc_status');

        $topVendors = Receive::select(
            'vendors.vendor_name',
            DB::raw('COUNT(receives.id) as total_receives'),
            DB::raw('SUM(receives.qty) as total_qty')
        )
            ->join('arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->join('arrivals', 'arrival_items.arrival_id', '=', 'arrivals.id')
            ->join('vendors', 'arrivals.vendor_id', '=', 'vendors.id')
            ->groupBy('vendors.vendor_name')
            ->orderByDesc('total_receives')
            ->limit(5)
            ->get();

        $summary = [
            'total_receives' => Receive::count(),
            'total_qty' => Receive::sum('qty'),
            'total_weight' => Receive::sum('weight'),
            'today' => Receive::whereDate('created_at', now())->count(),
        ];

        return view('receives.completed', compact('arrivals', 'statusCounts', 'topVendors', 'summary'));
    }

    public function completedInvoice(Arrival $arrival)
    {
        $arrival->load(['vendor', 'items.receives', 'containers.inspection']);

        $receives = Receive::query()
            ->select('receives.*')
            ->join('arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->join('parts', 'arrival_items.part_id', '=', 'parts.id')
            ->with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->where('arrival_items.arrival_id', $arrival->id)
            ->orderBy('parts.part_no', 'asc')
            ->orderByRaw('LENGTH(receives.tag) ASC')
            ->orderBy('receives.tag', 'asc')
            ->paginate(50);

        $remainingQtyTotal = collect($arrival->items)->sum(function ($item) {
            $received = $item->receives->sum('qty');
            return max(0, $item->qty_goods - $received);
        });
        $pendingItemsCount = $arrival->items->filter(function ($item) {
            $received = $item->receives->sum('qty');
            return ($item->qty_goods - $received) > 0;
        })->count();
        $hasMissingInspection = ($arrival->containers ?? collect())->isNotEmpty()
            && ($arrival->containers ?? collect())->contains(fn($c) => !$c->inspection);
        $hasMissingTag = $arrival->items
            ->flatMap(fn($i) => $i->receives ?? collect())
            ->contains(fn($r) => !is_string($r->tag) || trim($r->tag) === '');

        $hasPending = ($pendingItemsCount > 0) || $hasMissingInspection || $hasMissingTag;

        return view('receives.completed_invoice', compact(
            'arrival',
            'receives',
            'remainingQtyTotal',
            'pendingItemsCount',
            'hasPending',
            'hasMissingInspection',
            'hasMissingTag'
        ));
    }

    public function exportCompletedInvoice(Arrival $arrival)
    {
        $arrival->load(['vendor', 'items.receives', 'items.part']);

        if ($this->hasPendingReceives($arrival)) {
            return back()->with('error', 'Invoice ini belum complete receive.');
        }

        $filenameSafe = preg_replace('/[^A-Za-z0-9_.-]+/', '-', (string) ($arrival->invoice_no ?? 'invoice'));
        $filename = 'receives_' . $filenameSafe . '_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new CompletedInvoiceReceivesExport($arrival), $filename);
    }

    public function create(ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['part.vendor', 'arrival.vendor', 'arrival.containers.inspection', 'receives']);

        $totalReceived = $arrivalItem->receives->sum('qty');
        $remainingQty = max(0, $arrivalItem->qty_goods - $totalReceived);
        $totalPlanned = $arrivalItem->qty_goods;
        $defaultWeight = $arrivalItem->qty_goods > 0
            ? number_format($arrivalItem->weight_nett / $arrivalItem->qty_goods, 2, '.', '')
            : null;

        return view('receives.create', compact('arrivalItem', 'remainingQty', 'totalPlanned', 'totalReceived', 'defaultWeight'));
    }

    public function createByInvoice(Arrival $arrival)
    {
        $arrival->load(['vendor', 'containers.inspection', 'items.part', 'items.receives']);

        $pendingItems = $arrival->items
            ->map(function ($item) {
                $totalReceived = $item->receives->sum('qty');
                $remaining = $item->qty_goods - $totalReceived;
                $item->total_received = $totalReceived;
                $item->remaining_qty = max(0, $remaining);
                $item->default_weight = $item->qty_goods > 0
                    ? number_format($item->weight_nett / $item->qty_goods, 2, '.', '')
                    : null;
                return $item;
            })
            ->filter(fn($item) => $item->remaining_qty > 0)
            ->values();

        if ($pendingItems->isEmpty()) {
            if ($this->hasPendingReceives($arrival)) {
                return redirect()
                    ->route('receives.completed.invoice', $arrival)
                    ->with('error', 'Invoice belum bisa dianggap complete: pastikan inspection container dan TAG sudah lengkap.');
            }
            return redirect()->route('receives.completed.invoice', $arrival)->with('success', 'Semua item pada invoice ini sudah diterima.');
        }

        return view('receives.invoice', [
            'arrival' => $arrival,
            'pendingItems' => $pendingItems,
        ]);
    }

    public function store(Request $request, ArrivalItem $arrivalItem)
    {
        $arrivalItem->loadMissing(['arrival.vendor']);
        $isLocal = strtolower((string) ($arrivalItem->arrival?->vendor?->vendor_type ?? '')) === 'local';

        $locationCodeRule = ['nullable', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code');
        }

        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'truck_no' => $isLocal ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'tags' => 'required|array|min:1',
            'tags.*.tag' => 'required|string|max:255',
            'tags.*.qty' => 'required|integer|min:1',
            'tags.*.bundle_qty' => 'nullable|integer|min:0',
            'tags.*.bundle_unit' => 'required|in:PALLET,BUNDLE,BOX,BAG',
            'tags.*.location_code' => $locationCodeRule,
            // Backward compatible: old form used `weight`
            'tags.*.weight' => 'nullable|numeric',
            'tags.*.net_weight' => 'nullable|numeric',
            'tags.*.gross_weight' => 'nullable|numeric',
            'tags.*.qty_unit' => 'required|in:KGM,KG,PCS,COIL,SHEET,SET,EA',
            'tags.*.qc_status' => 'required|in:pass,reject',
        ]);

        $this->ensureTagsUniqueForArrivalItem($arrivalItem, $validated['tags'], 'tags');

        $totalRequested = collect($validated['tags'])->sum('qty');
        $totalReceived = $arrivalItem->receives()->sum('qty');
        $remainingQty = $arrivalItem->qty_goods - $totalReceived;

        if ($totalRequested > $remainingQty) {
            return back()
                ->withInput()
                ->withErrors([
                    'tags' => 'Total qty for tags (' . $totalRequested . ') exceeds remaining qty (' . $remainingQty . ').',
                ]);
        }

        $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');
        $partId = (int) $arrivalItem->part_id;
        $receiveQtyForInventory = 0;
        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));
        $truckNo = isset($validated['truck_no']) && trim((string) $validated['truck_no']) !== ''
            ? strtoupper(trim((string) $validated['truck_no']))
            : null;

        // --- BOM Validation (Strict) ---
        if ($partId) {
            $part = \App\Models\Part::with('gciPart')->find($partId);
            $gciPart = $part?->gciPart;

            if (!$gciPart) {
                // If not even linked to master, it's definitely not in BOM (unless partId IS master, but we use Vendor Part ID here)
                // But auto-linking should have handled this?
                // For now, if no GCI link, we can't validate BOM.
                // Should we block? User said: "barang yg belom ada di BOM ... di block".
                // If it has no internal ID, it's an orphan vendor part.
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tags' => "Part ini ({$part->part_no}) belum terdaftar di GCI Master Part. Harap hubungi Engineering/Admin.",
                ]);
            }

            // Check if GCI Part is used in any BOM
            if (!$gciPart->componentUsages()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tags' => "Part ini ({$gciPart->part_no} / {$part->part_no}) BELUM TERDAFTAR di Bill of Material (BOM) manapun. Receiving DITOLAK.",
                ]);
            }
        }
        // -------------------------------

        DB::transaction(function () use ($validated, $arrivalItem, $goodsUnit, $partId, $receiveAt, $truckNo, &$receiveQtyForInventory) {
            $locationAdds = [];
            foreach ($validated['tags'] as $tagData) {
                if (strtoupper($tagData['qty_unit']) !== $goodsUnit) {
                    throw new HttpResponseException(back()->withInput()->withErrors([
                        'tags' => "Unit qty tidak sesuai. Item ini menggunakan unit {$goodsUnit}.",
                    ]));
                }

                $netWeight = $tagData['net_weight'] ?? $tagData['weight'] ?? null;
                if ($netWeight === null && $goodsUnit === 'KGM') {
                    $netWeight = $tagData['qty'];
                }

                $locationCode = null;
                if (array_key_exists('location_code', $tagData)) {
                    $locationCode = strtoupper(trim((string) $tagData['location_code']));
                    if ($locationCode === '') {
                        $locationCode = null;
                    }
                }

                $arrivalItem->receives()->create([
                    'tag' => $tagData['tag'],
                    'qty' => $tagData['qty'],
                    'bundle_unit' => $tagData['bundle_unit'] ?? null,
                    'bundle_qty' => $tagData['bundle_qty'] ?? 0,
                    // Keep `weight` for existing reporting, mirror from net_weight
                    'weight' => $netWeight,
                    'net_weight' => $netWeight,
                    'gross_weight' => $tagData['gross_weight'] ?? null,
                    'qty_unit' => $goodsUnit,
                    'ata_date' => $receiveAt,
                    'qc_status' => $tagData['qc_status'] ?? 'pass',
                    'jo_po_number' => null,
                    'truck_no' => $truckNo,
                    'location_code' => $locationCode,
                ]);

                if (($tagData['qc_status'] ?? 'pass') === 'pass') {
                    $addQty = $goodsUnit === 'COIL' ? (float) ($netWeight ?? 0) : (float) $tagData['qty'];
                    $receiveQtyForInventory += $addQty;
                    if ($locationCode) {
                        $locationAdds[$locationCode] = ($locationAdds[$locationCode] ?? 0) + $addQty;
                    }
                }
            }

            if ($receiveQtyForInventory > 0 && $partId) {
                $inventory = Inventory::query()->where('part_id', $partId)->lockForUpdate()->first();
                if ($inventory) {
                    $inventory->update([
                        'on_hand' => (float) $inventory->on_hand + $receiveQtyForInventory,
                        'on_order' => max(0, (float) $inventory->on_order - $receiveQtyForInventory),
                        'as_of_date' => $receiveAt->toDateString(),
                    ]);
                } else {
                    Inventory::create([
                        'part_id' => $partId,
                        'on_hand' => $receiveQtyForInventory,
                        'on_order' => 0,
                        'as_of_date' => $receiveAt->toDateString(),
                    ]);
                }
            }

            // Putaway: update stock per location (pass only).
            if (!empty($locationAdds) && $partId) {
                foreach ($locationAdds as $locationCode => $qty) {
                    if ($qty > 0 && is_string($locationCode) && $locationCode !== '') {
                        LocationInventory::updateStock($partId, $locationCode, (float) $qty);
                    }
                }
            }
        });

        $arrival = $arrivalItem->arrival()->with('items.receives')->first();
        if ($arrival) {
            $message = !$this->hasPendingReceives($arrival)
                ? 'Invoice sudah complete receive.'
                : 'TAG tersimpan. Silakan cek summary (masih ada pending).';
            return redirect()->route('receives.completed.invoice', $arrival)->with('success', $message);
        }

        return redirect()->route('receives.index')->with('success', 'TAG tersimpan.');
    }

    public function storeByInvoice(Request $request, Arrival $arrival)
    {
        $arrival->loadMissing(['vendor', 'items.receives']);
        $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';

        $tagMode = $isLocal ? (string) $request->input('tag_mode', 'no_tag') : 'with_tag';
        if (!in_array($tagMode, ['with_tag', 'no_tag'], true)) {
            $tagMode = $isLocal ? 'no_tag' : 'with_tag';
        }

        $locationCodeRule = ['nullable', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code');
        }

        $rules = [
            'receive_date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'delivery_note_no' => ['nullable', 'string', 'max:100'],
            'truck_no' => $isLocal ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'tag_mode' => $isLocal ? ['required', 'in:with_tag,no_tag'] : ['nullable'],
            'delivery_note_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'packing_list_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
        ];

        if ($tagMode === 'no_tag') {
            $rules += [
                'items.*.summary' => ['nullable', 'array'],
                'items.*.summary.qty' => ['nullable', 'integer', 'min:0'],
                'items.*.summary.bundle_qty' => ['nullable', 'integer', 'min:0'],
                'items.*.summary.bundle_unit' => ['nullable', 'in:PALLET,BUNDLE,BOX,BAG,ROLL'],
                'items.*.summary.location_code' => $locationCodeRule,
                'items.*.summary.net_weight' => ['nullable', 'numeric'],
                'items.*.summary.gross_weight' => ['nullable', 'numeric'],
                'items.*.summary.qty_unit' => ['nullable', 'in:KGM,KG,PCS,COIL,SHEET,SET,EA'],
                'items.*.summary.qc_status' => ['nullable', 'in:pass,reject'],
            ];
        } else {
            $rules += [
                'items.*.tags' => ['nullable', 'array'],
                'items.*.tags.*.tag' => ['required_with:items.*.tags', 'string', 'max:255'],
                'items.*.tags.*.qty' => ['required_with:items.*.tags', 'integer', 'min:1'],
                'items.*.tags.*.bundle_qty' => ['nullable', 'integer', 'min:0'],
                'items.*.tags.*.bundle_unit' => ['required_with:items.*.tags', 'in:PALLET,BUNDLE,BOX,BAG,ROLL'],
                'items.*.tags.*.location_code' => $locationCodeRule,
                // Backward compatible: old form used `weight`
                'items.*.tags.*.weight' => ['nullable', 'numeric'],
                'items.*.tags.*.net_weight' => ['nullable', 'numeric'],
                'items.*.tags.*.gross_weight' => ['nullable', 'numeric'],
                'items.*.tags.*.qty_unit' => ['required_with:items.*.tags', 'in:KGM,KG,PCS,COIL,SHEET,SET,EA,UOM'],
                'items.*.tags.*.qc_status' => ['required_with:items.*.tags', 'in:pass,reject'],
            ];
        }

        $validated = $request->validate($rules);

        $itemsInput = [];
        if ($tagMode === 'no_tag') {
            foreach (($validated['items'] ?? []) as $itemId => $itemData) {
                $summary = is_array($itemData['summary'] ?? null) ? $itemData['summary'] : null;
                $qty = (int) ($summary['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $itemsInput[$itemId] = [
                    'tags' => [
                        [
                            'tag' => null,
                            'qty' => $qty,
                            'bundle_qty' => (int) ($summary['bundle_qty'] ?? 0),
                            'bundle_unit' => $summary['bundle_unit'] ?? null,
                            'location_code' => $summary['location_code'] ?? null,
                            'net_weight' => $summary['net_weight'] ?? null,
                            'gross_weight' => $summary['gross_weight'] ?? null,
                            'qty_unit' => $summary['qty_unit'] ?? null,
                            'qc_status' => $summary['qc_status'] ?? 'pass',
                        ]
                    ],
                ];
            }
        } else {
            $itemsInput = collect($validated['items'])
                ->filter(fn($item) => !empty($item['tags']))
                ->all();
        }

        if (empty($itemsInput)) {
            return back()->withErrors([
                'items' => $tagMode === 'no_tag'
                    ? 'Isi minimal satu item (qty) untuk receive.'
                    : 'Tambah minimal satu tag pada salah satu item.',
            ])->withInput();
        }

        foreach ($itemsInput as $itemId => $itemData) {
            $arrivalItem = $arrival->items->firstWhere('id', $itemId);
            if (!$arrivalItem) {
                continue;
            }
            $this->ensureTagsUniqueForArrivalItem($arrivalItem, $itemData['tags'] ?? [], "items.$itemId.tags");
        }

        foreach ($itemsInput as $itemId => $itemData) {
            $arrivalItem = $arrival->items->firstWhere('id', $itemId);
            if (!$arrivalItem) {
                return back()->withErrors(['items' => 'Item tidak ditemukan pada invoice ini.'])->withInput();
            }

            $totalRequested = collect($itemData['tags'])->sum('qty');
            $totalReceived = $arrivalItem->receives->sum('qty');
            $remainingQty = $arrivalItem->qty_goods - $totalReceived;

            if ($totalRequested > $remainingQty) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "items.$itemId.tags" => "Total qty untuk item {$arrivalItem->part->part_no} ({$totalRequested}) melebihi sisa ({$remainingQty}).",
                    ]);
            }
        }

        $inventoryAdds = [];
        $locationAdds = [];
        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));
        $truckNo = isset($validated['truck_no']) && trim((string) $validated['truck_no']) !== ''
            ? strtoupper(trim((string) $validated['truck_no']))
            : null;

        DB::transaction(function () use ($itemsInput, $arrival, $receiveAt, $truckNo, &$inventoryAdds, &$locationAdds, $request, $validated) {
            foreach ($itemsInput as $itemId => $itemData) {
                $arrivalItem = $arrival->items->firstWhere('id', $itemId);
                $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

                // --- BOM Validation (Strict) ---
                $partId = (int) $arrivalItem->part_id;
                if ($partId) {
                    $part = \App\Models\Part::with('gciPart')->find($partId);
                    $gciPart = $part?->gciPart;

                    if (!$gciPart) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.$itemId.tags" => "Part ini ({$part->part_no}) belum terdaftar di GCI Master Part.",
                        ]);
                    }
                    if (!$gciPart->componentUsages()->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.$itemId.tags" => "Part ini ({$gciPart->part_no}) BELUM TERDAFTAR di BOM manapun. Receiving DITOLAK.",
                        ]);
                    }
                }
                // -------------------------------

                foreach ($itemData['tags'] as $tagData) {
                    if (strtoupper($tagData['qty_unit'] ?? '') !== $goodsUnit) {
                        throw new HttpResponseException(back()->withInput()->withErrors([
                            "items.$itemId.tags" => "Unit qty tidak sesuai. Item ini menggunakan unit {$goodsUnit}.",
                        ]));
                    }

                    $netWeight = $tagData['net_weight'] ?? $tagData['weight'] ?? null;
                    if ($netWeight === null && $goodsUnit === 'KGM') {
                        $netWeight = $tagData['qty'];
                    }
                    $locationCode = null;
                    if (array_key_exists('location_code', $tagData)) {
                        $locationCode = strtoupper(trim((string) $tagData['location_code']));
                        if ($locationCode === '') {
                            $locationCode = null;
                        }
                    }
                    $arrivalItem->receives()->create([
                        'tag' => $tagData['tag'] ?? null,
                        'qty' => $tagData['qty'],
                        'bundle_unit' => $tagData['bundle_unit'] ?? null,
                        'bundle_qty' => $tagData['bundle_qty'] ?? 0,
                        // Keep `weight` for existing reporting, mirror from net_weight
                        'weight' => $netWeight,
                        'net_weight' => $netWeight,
                        'gross_weight' => $tagData['gross_weight'] ?? null,
                        'qty_unit' => $goodsUnit,
                        'ata_date' => $receiveAt,
                        'qc_status' => $tagData['qc_status'] ?? 'pass',
                        'jo_po_number' => null,
                        'invoice_no' => $validated['invoice_no'] ?? null,
                        'delivery_note_no' => $validated['delivery_note_no'] ?? null,
                        'truck_no' => $truckNo,
                        'location_code' => $locationCode,
                    ]);

                    if (($tagData['qc_status'] ?? 'pass') === 'pass') {
                        $partId = (int) $arrivalItem->part_id;
                        $addQty = $goodsUnit === 'COIL' ? (float) ($netWeight ?? 0) : (float) $tagData['qty'];
                        $inventoryAdds[$partId] = ($inventoryAdds[$partId] ?? 0) + $addQty;
                        if ($locationCode) {
                            $locationAdds[$partId][$locationCode] = ($locationAdds[$partId][$locationCode] ?? 0) + $addQty;
                        }
                    }
                }
            }

            foreach ($inventoryAdds as $partId => $qty) {
                if ($qty <= 0 || !$partId) {
                    continue;
                }
                $inventory = Inventory::query()->where('part_id', $partId)->lockForUpdate()->first();
                if ($inventory) {
                    $inventory->update([
                        'on_hand' => (float) $inventory->on_hand + $qty,
                        'on_order' => max(0, (float) $inventory->on_order - $qty),
                        'as_of_date' => $receiveAt->toDateString(),
                    ]);
                } else {
                    Inventory::create([
                        'part_id' => $partId,
                        'on_hand' => $qty,
                        'on_order' => 0,
                        'as_of_date' => $receiveAt->toDateString(),
                    ]);
                }
            }

            // Putaway: update stock per location (pass only).
            foreach ($locationAdds as $partId => $byLocation) {
                if (!$partId || empty($byLocation) || !is_array($byLocation)) {
                    continue;
                }
                foreach ($byLocation as $locationCode => $qty) {
                    if ($qty > 0 && is_string($locationCode) && $locationCode !== '') {
                        LocationInventory::updateStock((int) $partId, $locationCode, (float) $qty);
                    }
                }
            }
            $dir = "local_pos/arrival-{$arrival->id}";

            if ($request->hasFile('delivery_note_file')) {
                $file = $request->file('delivery_note_file');
                $path = $file->storePubliclyAs($dir, "surat_jalan." . $file->getClientOriginalExtension(), 'public');
                $arrival->delivery_note_file = $path;
                $arrival->save();
            }

            if ($request->hasFile('invoice_file')) {
                $file = $request->file('invoice_file');
                $path = $file->storePubliclyAs($dir, "invoice." . $file->getClientOriginalExtension(), 'public');
                $arrival->invoice_file = $path;
                $arrival->save();
            }

            if ($request->hasFile('packing_list_file')) {
                $file = $request->file('packing_list_file');
                $path = $file->storePubliclyAs($dir, "packing_list." . $file->getClientOriginalExtension(), 'public');
                $arrival->packing_list_file = $path;
                $arrival->save();
            }
        });

        $arrival->load('items.receives');
        $message = !$this->hasPendingReceives($arrival)
            ? 'Invoice sudah complete receive.'
            : 'TAG tersimpan. Silakan cek summary (masih ada pending).';

        return redirect()->route('receives.completed.invoice', $arrival)->with('success', $message);
    }

    public function printLabel(Receive $receive)
    {
        $receive->load(['arrivalItem.part', 'arrivalItem.arrival.vendor']);
        $arrivalItem = $receive->arrivalItem;
        $arrival = $arrivalItem?->arrival;
        $part = $arrivalItem?->part;

        $receivedAt = $receive->ata_date ?? now();
        $monthNumber = (int) $receivedAt->format('m');

        $warehouseLocation = null;
        $locCode = is_string($receive->location_code) ? strtoupper(trim($receive->location_code)) : '';
        if ($locCode !== '' && Schema::hasTable('warehouse_locations')) {
            $warehouseLocation = WarehouseLocation::query()->where('location_code', $locCode)->first();
        }

        // Update QR Payload to JSON (Unified Standard)
        // This ensures the App receives Part No and GCI No for accurate inventory tracking.
        $payload = [
            'tag' => (string) ($receive->tag ?? $receive->id),
            'part_no' => (string) ($part?->part_no ?? ''),
            'gci_part_no' => (string) ($part?->gciPart?->part_no ?? ''), // The Bridge
            'qty' => (float) $receive->qty,
            'invoice' => (string) ($arrival?->invoice_no ?? '-'),
        ];

        $payloadString = json_encode($payload);

        $qrSvg = QrSvg::make($payloadString, 400, 0);

        return view('receives.label', compact('receive', 'qrSvg', 'monthNumber', 'warehouseLocation'));
    }

    public function edit(Receive $receive)
    {
        $receive->load(['arrivalItem.part', 'arrivalItem.arrival.vendor']);

        return view('receives.edit', [
            'receive' => $receive,
            'arrival' => $receive->arrivalItem->arrival,
            'arrivalItem' => $receive->arrivalItem,
        ]);
    }

    public function update(Request $request, Receive $receive)
    {
        $receive->load(['arrivalItem.arrival', 'arrivalItem.part']);
        $arrivalItem = $receive->arrivalItem;
        $arrival = $arrivalItem->arrival;
        $isLocal = strtolower((string) ($arrival->vendor?->vendor_type ?? '')) === 'local';

        $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

        $locationCodeRule = ['nullable', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code');
        }

        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'delivery_note_no' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:255'],
            'location_code' => $locationCodeRule,
            'truck_no' => $isLocal ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'bundle_qty' => ['nullable', 'integer', 'min:0'],
            'bundle_unit' => ['required', 'in:PALLET,BUNDLE,BOX,BAG,ROLL'],
            'qty' => ['required', 'integer', 'min:1'],
            'net_weight' => ['nullable', 'numeric'],
            'gross_weight' => ['nullable', 'numeric'],
            'qc_status' => ['required', 'in:pass,reject'],
        ]);

        $tag = $this->normalizeTag($validated['tag'] ?? null);
        $locationCode = array_key_exists('location_code', $validated)
            ? strtoupper(trim((string) $validated['location_code']))
            : null;
        if ($locationCode === '') {
            $locationCode = null;
        }

        if (($validated['net_weight'] ?? null) !== null && ($validated['gross_weight'] ?? null) !== null) {
            if ((float) $validated['net_weight'] > (float) $validated['gross_weight']) {
                return back()->withInput()->withErrors([
                    'net_weight' => 'Net weight harus lebih kecil atau sama dengan gross weight.',
                ]);
            }
        }

        // Check tag uniqueness within this arrival item (ignore current receive).
        if ($tag !== null) {
            $this->ensureTagsUniqueForArrivalItem(
                $arrivalItem,
                [['tag' => $tag]],
                'tag',
                (int) $receive->id
            );
        }

        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));

        $oldLocationCode = is_string($receive->location_code) ? strtoupper(trim((string) $receive->location_code)) : '';
        if ($oldLocationCode === '') {
            $oldLocationCode = null;
        }

        $oldQty = (float) $receive->qty;
        $oldPass = $receive->qc_status === 'pass';
        $oldContribution = $oldPass
            ? ($goodsUnit === 'COIL'
                ? (float) ($receive->net_weight ?? $receive->weight ?? $receive->qty ?? 0)
                : $oldQty)
            : 0.0;

        $newQty = (float) $validated['qty'];
        $newPass = $validated['qc_status'] === 'pass';
        $newContribution = $newPass
            ? ($goodsUnit === 'COIL'
                ? (float) ($validated['net_weight'] ?? $validated['weight'] ?? $validated['qty'] ?? 0)
                : $newQty)
            : 0.0;

        $delta = $newContribution - $oldContribution;

        DB::transaction(function () use ($receive, $arrivalItem, $goodsUnit, $validated, $tag, $locationCode, $oldLocationCode, $oldContribution, $newContribution, $receiveAt, $delta) {
            $truckNo = isset($validated['truck_no']) && trim((string) $validated['truck_no']) !== ''
                ? strtoupper(trim((string) $validated['truck_no']))
                : null;

            // Update receive row
            $receive->update([
                'tag' => $tag,
                'qty' => (int) $validated['qty'],
                'bundle_unit' => $validated['bundle_unit'],
                'bundle_qty' => (int) ($validated['bundle_qty'] ?? 0),
                'weight' => $validated['net_weight'] ?? $validated['weight'] ?? null,
                'net_weight' => $validated['net_weight'] ?? $validated['weight'] ?? null,
                'gross_weight' => $validated['gross_weight'] ?? null,
                'qty_unit' => $goodsUnit,
                'ata_date' => $receiveAt,
                'qc_status' => $validated['qc_status'],
                'invoice_no' => $validated['invoice_no'] ?? null,
                'delivery_note_no' => $validated['delivery_note_no'] ?? null,
                'truck_no' => $truckNo,
                'location_code' => $locationCode,
            ]);

            // Keep location stock consistent with pass qty.
            $partId = (int) $arrivalItem->part_id;
            if ($partId) {
                if ($oldLocationCode && $oldContribution > 0) {
                    LocationInventory::updateStock($partId, $oldLocationCode, -$oldContribution);
                }
                if ($locationCode && $newContribution > 0) {
                    LocationInventory::updateStock($partId, $locationCode, $newContribution);
                }
            }

            if ($delta == 0.0) {
                return;
            }

            $inventory = Inventory::query()->where('part_id', $partId)->lockForUpdate()->first();
            if (!$inventory) {
                if ($delta < 0) {
                    throw new HttpResponseException(back()->withInput()->withErrors([
                        'qty' => 'Inventory tidak ditemukan untuk part ini, tidak bisa mengurangi stok.',
                    ]));
                }
                Inventory::create([
                    'part_id' => $partId,
                    'on_hand' => $delta,
                    'on_order' => 0,
                    'as_of_date' => $receiveAt->toDateString(),
                ]);
                return;
            }

            $newOnHand = (float) $inventory->on_hand + $delta;
            if ($newOnHand < 0) {
                throw new HttpResponseException(back()->withInput()->withErrors([
                    'qty' => 'Perubahan qty menyebabkan inventory menjadi minus.',
                ]));
            }

            $inventory->update([
                'on_hand' => $newOnHand,
                // Mirror receive adjustment to on_order (best-effort)
                'on_order' => max(0, (float) $inventory->on_order - $delta),
                'as_of_date' => $receiveAt->toDateString(),
            ]);
        });

        return redirect()
            ->route('receives.completed.invoice', $arrival)
            ->with('success', 'Receive berhasil diupdate.');
    }
}
