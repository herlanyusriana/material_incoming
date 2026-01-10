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
use App\Exports\CompletedInvoiceReceivesExport;
use Maatwebsite\Excel\Facades\Excel;

class ReceiveController extends Controller
{
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

    private function ensureTagsUniqueForArrivalItem(ArrivalItem $arrivalItem, array $tags): void
    {
        $incomingTags = collect($tags)
            ->pluck('tag')
            ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn ($tag) => strtoupper(trim($tag)))
            ->values();

        if ($incomingTags->isEmpty()) {
            return;
        }

        $duplicatesInRequest = $incomingTags
            ->countBy()
            ->filter(fn ($count) => $count > 1)
            ->keys()
            ->values();

        if ($duplicatesInRequest->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                'tags' => 'Ada TAG duplikat di input: ' . $duplicatesInRequest->implode(', '),
            ]));
        }

        $existingTags = $arrivalItem->receives()
            ->whereIn('tag', $incomingTags->all())
            ->pluck('tag')
            ->map(fn ($tag) => strtoupper(trim((string) $tag)))
            ->unique()
            ->values();

        if ($existingTags->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                'tags' => 'TAG sudah pernah diinput untuk item ini: ' . $existingTags->implode(', '),
            ]));
        }
    }

    private function ensureTagsUniqueForArrival(Arrival $arrival, array $itemsInput): void
    {
        $incomingTags = collect($itemsInput)
            ->flatMap(function ($itemData) {
                return collect($itemData['tags'] ?? [])->pluck('tag');
            })
            ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
            ->map(fn ($tag) => strtoupper(trim($tag)))
            ->values();

        if ($incomingTags->isEmpty()) {
            return;
        }

        $duplicatesInRequest = $incomingTags
            ->countBy()
            ->filter(fn ($count) => $count > 1)
            ->keys()
            ->values();

        if ($duplicatesInRequest->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                'items' => 'Ada TAG duplikat di input: ' . $duplicatesInRequest->implode(', '),
            ]));
        }

        $existingTags = Receive::query()
            ->whereIn('tag', $incomingTags->all())
            ->whereHas('arrivalItem', fn ($q) => $q->where('arrival_id', $arrival->id))
            ->pluck('tag')
            ->map(fn ($tag) => strtoupper(trim((string) $tag)))
            ->unique()
            ->values();

        if ($existingTags->isNotEmpty()) {
            throw new HttpResponseException(back()->withInput()->withErrors([
                'items' => 'TAG sudah pernah diinput untuk invoice ini: ' . $existingTags->implode(', '),
            ]));
        }
    }

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
                DB::raw('COUNT(receives.id) as receives_count'),
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
        $arrival->load(['vendor', 'items.receives']);

        $receives = Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->whereHas('arrivalItem', fn ($q) => $q->where('arrival_id', $arrival->id))
            ->latest()
            ->paginate(25);

        $remainingQtyTotal = $arrival->items->sum(function ($item) {
            $received = $item->receives->sum('qty');
            return max(0, $item->qty_goods - $received);
        });
        $pendingItemsCount = $arrival->items->filter(function ($item) {
            $received = $item->receives->sum('qty');
            return ($item->qty_goods - $received) > 0;
        })->count();
        $hasPending = $pendingItemsCount > 0;

        return view('receives.completed_invoice', compact('arrival', 'receives', 'remainingQtyTotal', 'pendingItemsCount', 'hasPending'));
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
            ->filter(fn ($item) => $item->remaining_qty > 0)
            ->values();

        if ($pendingItems->isEmpty()) {
            return redirect()->route('receives.completed.invoice', $arrival)->with('success', 'Semua item pada invoice ini sudah diterima.');
        }

        return view('receives.invoice', [
            'arrival' => $arrival,
            'pendingItems' => $pendingItems,
        ]);
    }

    public function store(Request $request, ArrivalItem $arrivalItem)
    {
        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'tags' => 'required|array|min:1',
            'tags.*.tag' => 'required|string|max:255',
            'tags.*.qty' => 'required|integer|min:1',
            'tags.*.bundle_qty' => 'nullable|integer|min:1',
            'tags.*.bundle_unit' => 'required|in:PALLET,BUNDLE,BOX',
            'tags.*.location_code' => 'nullable|string|max:50',
            // Backward compatible: old form used `weight`
            'tags.*.weight' => 'nullable|numeric',
            'tags.*.net_weight' => 'nullable|numeric',
            'tags.*.gross_weight' => 'nullable|numeric',
            'tags.*.qty_unit' => 'required|in:KGM,KG,PCS,COIL,SHEET,SET,EA',
            'tags.*.qc_status' => 'required|in:pass,reject',
        ]);

        $this->ensureTagsUniqueForArrivalItem($arrivalItem, $validated['tags']);

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

        DB::transaction(function () use ($validated, $arrivalItem, $goodsUnit, $partId, $receiveAt, &$receiveQtyForInventory) {
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
                    'bundle_qty' => $tagData['bundle_qty'] ?? 1,
                    // Keep `weight` for existing reporting, mirror from net_weight
                    'weight' => $netWeight,
                    'net_weight' => $netWeight,
                    'gross_weight' => $tagData['gross_weight'] ?? null,
                    'qty_unit' => $goodsUnit,
                    'ata_date' => $receiveAt,
                    'qc_status' => $tagData['qc_status'] ?? 'pass',
                    'jo_po_number' => null,
                    'location_code' => $locationCode,
                ]);

                if (($tagData['qc_status'] ?? 'pass') === 'pass') {
                    $receiveQtyForInventory += (float) $tagData['qty'];
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
        $arrival->load('items.receives');

        $validated = $request->validate([
            'receive_date' => ['required', 'date'],
            'items' => 'required|array|min:1',
            'items.*.tags' => 'nullable|array',
            'items.*.tags.*.tag' => 'required_with:items.*.tags|string|max:255',
            'items.*.tags.*.qty' => 'required_with:items.*.tags|integer|min:1',
            'items.*.tags.*.bundle_qty' => 'nullable|integer|min:1',
            'items.*.tags.*.bundle_unit' => 'required_with:items.*.tags|in:PALLET,BUNDLE,BOX',
            'items.*.tags.*.location_code' => 'nullable|string|max:50',
            // Backward compatible: old form used `weight`
            'items.*.tags.*.weight' => 'nullable|numeric',
            'items.*.tags.*.net_weight' => 'nullable|numeric',
            'items.*.tags.*.gross_weight' => 'nullable|numeric',
            'items.*.tags.*.qty_unit' => 'required_with:items.*.tags|in:KGM,KG,PCS,COIL,SHEET,SET,EA',
            'items.*.tags.*.qc_status' => 'required_with:items.*.tags|in:pass,reject',
        ]);

        $itemsInput = collect($validated['items'])
            ->filter(fn ($item) => !empty($item['tags']))
            ->all();

        if (empty($itemsInput)) {
            return back()->withErrors(['items' => 'Tambah minimal satu tag pada salah satu item.'])->withInput();
        }

        $this->ensureTagsUniqueForArrival($arrival, $itemsInput);

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
        $receiveAt = Carbon::parse($validated['receive_date'])->setTimeFromTimeString(now()->format('H:i:s'));

        DB::transaction(function () use ($itemsInput, $arrival, $receiveAt, &$inventoryAdds) {
            foreach ($itemsInput as $itemId => $itemData) {
                $arrivalItem = $arrival->items->firstWhere('id', $itemId);
                $goodsUnit = strtoupper($arrivalItem->unit_goods ?? 'KGM');

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
                        'tag' => $tagData['tag'],
                        'qty' => $tagData['qty'],
                        'bundle_unit' => $tagData['bundle_unit'] ?? null,
                        'bundle_qty' => $tagData['bundle_qty'] ?? 1,
                        // Keep `weight` for existing reporting, mirror from net_weight
                        'weight' => $netWeight,
                        'net_weight' => $netWeight,
                        'gross_weight' => $tagData['gross_weight'] ?? null,
                        'qty_unit' => $goodsUnit,
                        'ata_date' => $receiveAt,
                        'qc_status' => $tagData['qc_status'] ?? 'pass',
                        'jo_po_number' => null,
                        'location_code' => $locationCode,
                    ]);

                    if (($tagData['qc_status'] ?? 'pass') === 'pass') {
                        $partId = (int) $arrivalItem->part_id;
                        $inventoryAdds[$partId] = ($inventoryAdds[$partId] ?? 0) + (float) $tagData['qty'];
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
        return view('receives.label', compact('receive'));
    }
}
