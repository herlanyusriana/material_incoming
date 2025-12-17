<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Receive;
use App\Models\ArrivalItem;
use App\Models\Arrival;

class ReceiveController extends Controller
{
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
        $arrival->load('vendor');

        $receives = Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->whereHas('arrivalItem', fn ($q) => $q->where('arrival_id', $arrival->id))
            ->latest()
            ->paginate(25);

        return view('receives.completed_invoice', compact('arrival', 'receives'));
    }

    public function create(ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['part.vendor', 'arrival.vendor', 'receives']);

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
        $arrival->load(['vendor', 'items.part', 'items.receives']);

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
            return redirect()->route('receives.index')->with('success', 'Semua item pada invoice ini sudah diterima.');
        }

        return view('receives.invoice', [
            'arrival' => $arrival,
            'pendingItems' => $pendingItems,
        ]);
    }

    public function store(Request $request, ArrivalItem $arrivalItem)
    {
        $validated = $request->validate([
            'tags' => 'required|array|min:1',
            'tags.*.tag' => 'required|string|max:255',
            'tags.*.qty' => 'required|integer|min:1',
            'tags.*.bundle_unit' => 'required|string|max:20',
            'tags.*.weight' => 'nullable|numeric',
            'tags.*.qty_unit' => 'required|string|max:20',
            'tags.*.qc_status' => 'required|in:pass,reject',
        ]);

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

        // Create a receive record for each tag with default values
        foreach ($validated['tags'] as $tagData) {
            $arrivalItem->receives()->create([
                'tag' => $tagData['tag'],
                'qty' => $tagData['qty'],
                'bundle_unit' => $tagData['bundle_unit'] ?? null,
                'weight' => $tagData['weight'] ?? null,
                'qty_unit' => $tagData['qty_unit'] ?? null,
                'ata_date' => now(),
                'qc_status' => $tagData['qc_status'] ?? 'pass',
                'jo_po_number' => null,
                'location_code' => null,
            ]);
        }

        return redirect()->route('receives.index')->with('success', 'Items received successfully with ' . count($validated['tags']) . ' tag(s).');
    }

    public function storeByInvoice(Request $request, Arrival $arrival)
    {
        $arrival->load('items.receives');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.tags' => 'nullable|array',
            'items.*.tags.*.tag' => 'required_with:items.*.tags|string|max:255',
            'items.*.tags.*.qty' => 'required_with:items.*.tags|integer|min:1',
            'items.*.tags.*.bundle_unit' => 'required_with:items.*.tags|string|max:20',
            'items.*.tags.*.weight' => 'nullable|numeric',
            'items.*.tags.*.qty_unit' => 'required_with:items.*.tags|string|max:20',
            'items.*.tags.*.qc_status' => 'required_with:items.*.tags|in:pass,reject',
        ]);

        $itemsInput = collect($validated['items'])
            ->filter(fn ($item) => !empty($item['tags']))
            ->all();

        if (empty($itemsInput)) {
            return back()->withErrors(['items' => 'Tambah minimal satu tag pada salah satu item.'])->withInput();
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

        foreach ($itemsInput as $itemId => $itemData) {
            $arrivalItem = $arrival->items->firstWhere('id', $itemId);
            foreach ($itemData['tags'] as $tagData) {
                $arrivalItem->receives()->create([
                    'tag' => $tagData['tag'],
                    'qty' => $tagData['qty'],
                    'bundle_unit' => $tagData['bundle_unit'] ?? null,
                    'weight' => $tagData['weight'] ?? null,
                    'qty_unit' => $tagData['qty_unit'] ?? null,
                    'ata_date' => now(),
                    'qc_status' => $tagData['qc_status'] ?? 'pass',
                    'jo_po_number' => null,
                    'location_code' => null,
                ]);
            }
        }

        return redirect()->route('receives.index')->with('success', 'Berhasil menerima item untuk invoice ' . $arrival->invoice_no . '.');
    }

    public function printLabel(Receive $receive)
    {
        $receive->load(['arrivalItem.part', 'arrivalItem.arrival.vendor']);
        return view('receives.label', compact('receive'));
    }
}
