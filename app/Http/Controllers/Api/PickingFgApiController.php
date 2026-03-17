<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\DeliveryOrder;
use App\Models\OutgoingPickingFg;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PickingFgApiController extends Controller
{
    /**
     * List all picking items for a date (flat list — legacy).
     */
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $picks = OutgoingPickingFg::with(['part', 'outgoingPoItem.outgoingPo', 'deliveryOrder.deliveryNotes'])
            ->where('delivery_date', $date)
            ->get();

        // Filter out picks whose DO already has a Delivery Note
        $picks = $picks->filter(function ($p) {
            if ($p->delivery_order_id && $p->deliveryOrder) {
                return $p->deliveryOrder->deliveryNotes->isEmpty();
            }
            return true;
        })->values();

        $lastUpdated = $picks->max('updated_at');

        // Pre-compute DO completion status
        $doCompletedMap = [];
        $doIds = $picks->pluck('delivery_order_id')->filter()->unique()->toArray();
        foreach ($doIds as $doId) {
            $pendingCount = OutgoingPickingFg::where('delivery_order_id', $doId)
                ->where('status', '!=', 'completed')
                ->count();
            $doCompletedMap[$doId] = $pendingCount === 0;
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'last_updated' => $lastUpdated?->toIso8601String(),
            'data' => $picks->map(function ($p) use ($doCompletedMap) {
                $stockLocations = LocationInventory::where('gci_part_id', $p->gci_part_id)
                    ->where('qty_on_hand', '>', 0)
                    ->orderByDesc('qty_on_hand')
                    ->limit(5)
                    ->get()
                    ->map(fn($loc) => [
                        'code' => $loc->location_code,
                        'qty' => (float) $loc->qty_on_hand,
                        'batch_no' => $loc->batch_no,
                    ])->toArray();

                return [
                    'id' => $p->id,
                    'part_id' => $p->gci_part_id,
                    'part_no' => $p->part->part_no ?? 'N/A',
                    'part_name' => $p->part->part_name ?? 'N/A',
                    'model' => $p->part->model ?? '-',
                    'qty_plan' => (int) $p->qty_plan,
                    'qty_picked' => (int) $p->qty_picked,
                    'qty_remaining' => $p->qty_remaining,
                    'status' => $p->status,
                    'location' => $p->pick_location,
                    'expected_location' => $p->part->default_location ?? null,
                    'stock_locations' => $stockLocations,
                    'progress' => $p->progress_percent,
                    'source' => $p->source ?? 'daily_plan',
                    'po_no' => $p->outgoingPoItem?->outgoingPo?->po_no,
                    'delivery_order_id' => $p->delivery_order_id,
                    'do_no' => $p->deliveryOrder?->do_no,
                    'trip_no' => $p->deliveryOrder?->trip_no,
                    'do_completed' => $doCompletedMap[$p->delivery_order_id] ?? false,
                    'updated_at' => $p->updated_at?->toIso8601String(),
                ];
            })
        ]);
    }

    /**
     * Summary status for a date.
     */
    public function status(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $picks = OutgoingPickingFg::where('delivery_date', $date);

        return response()->json([
            'success' => true,
            'date' => $date,
            'last_updated' => (clone $picks)->max('updated_at'),
            'total' => (clone $picks)->count(),
            'pending' => (clone $picks)->where('status', 'pending')->count(),
            'picking' => (clone $picks)->where('status', 'picking')->count(),
            'completed' => (clone $picks)->where('status', 'completed')->count(),
            'qty_plan' => (clone $picks)->sum('qty_plan'),
            'qty_picked' => (clone $picks)->sum('qty_picked'),
        ]);
    }

    /**
     * Lookup part by part_no or barcode (legacy).
     */
    public function lookupPart(Request $request)
    {
        $request->validate(['part_no' => 'required|string', 'date' => 'required|date']);

        $partNo = trim((string) $request->part_no);
        $part = GciPart::where('part_no', $partNo)
            ->orWhere('barcode', $partNo)
            ->first();
        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        $picks = OutgoingPickingFg::with('deliveryOrder')
            ->where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id)
            ->where('status', '!=', 'completed')
            ->get();

        if ($picks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending picking for this part on this date',
            ], 404);
        }

        $stockLocations = LocationInventory::where('gci_part_id', $part->id)
            ->where('qty_on_hand', '>', 0)
            ->orderByDesc('qty_on_hand')
            ->limit(5)
            ->get()
            ->map(fn($loc) => [
                'code' => $loc->location_code,
                'qty' => (float) $loc->qty_on_hand,
                'batch_no' => $loc->batch_no,
            ])->toArray();

        return response()->json([
            'success' => true,
            'part' => [
                'id' => $part->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'barcode' => $part->barcode,
                'model' => $part->model ?? '-',
                'expected_location' => $part->default_location ?? null,
                'stock_locations' => $stockLocations,
            ],
            'picks' => $picks->map(fn($p) => [
                'id' => $p->id,
                'delivery_order_id' => $p->delivery_order_id,
                'do_no' => $p->deliveryOrder?->do_no ?? 'N/A',
                'trip_no' => $p->deliveryOrder?->trip_no,
                'qty_plan' => (int) $p->qty_plan,
                'qty_picked' => (int) $p->qty_picked,
                'qty_remaining' => $p->qty_remaining,
                'status' => $p->status,
            ]),
        ]);
    }

    // ─── DO-BASED PICKING FLOW ───────────────────────────────────────

    /**
     * Step 1: List Delivery Orders that have picking tasks.
     * GET /api/picking-fg/delivery-orders?date=YYYY-MM-DD
     */
    public function deliveryOrders(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $doIds = OutgoingPickingFg::where('delivery_date', $date)
            ->whereNotNull('delivery_order_id')
            ->pluck('delivery_order_id')
            ->unique()
            ->toArray();

        if (empty($doIds)) {
            return response()->json([
                'success' => true,
                'date' => $date,
                'data' => [],
            ]);
        }

        $orders = DeliveryOrder::with('customer')
            ->whereIn('id', $doIds)
            ->get();

        // Filter out DOs that already have a DN
        $orders = $orders->filter(function ($do) {
            return $do->deliveryNotes()->count() === 0;
        })->values();

        $data = $orders->map(function ($do) use ($date) {
            $picks = OutgoingPickingFg::where('delivery_order_id', $do->id)
                ->where('delivery_date', $date)
                ->get();

            $totalPlan = $picks->sum('qty_plan');
            $totalPicked = $picks->sum('qty_picked');

            return [
                'id' => $do->id,
                'do_no' => $do->do_no,
                'trip_no' => $do->trip_no,
                'customer' => [
                    'id' => $do->customer?->id,
                    'code' => $do->customer?->code,
                    'name' => $do->customer?->name,
                ],
                'do_date' => $do->do_date?->format('Y-m-d'),
                'status' => $do->status,
                'items_count' => $picks->count(),
                'qty_plan' => (int) $totalPlan,
                'qty_picked' => (int) $totalPicked,
                'progress' => $totalPlan > 0 ? round(($totalPicked / $totalPlan) * 100, 1) : 0,
                'all_completed' => $picks->every(fn($p) => $p->status === 'completed'),
            ];
        });

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $data,
        ]);
    }

    /**
     * Step 2: Get DO detail — list parts to pick with stock locations.
     * GET /api/picking-fg/delivery-orders/{id}?date=YYYY-MM-DD
     */
    public function deliveryOrderDetail(Request $request, int $id)
    {
        $date = $request->query('date', now()->toDateString());

        $do = DeliveryOrder::with('customer')->findOrFail($id);

        $picks = OutgoingPickingFg::with(['part', 'outgoingPoItem.outgoingPo'])
            ->where('delivery_order_id', $id)
            ->where('delivery_date', $date)
            ->get();

        if ($picks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No picking tasks for this DO on this date',
            ], 404);
        }

        $items = $picks->map(function ($p) {
            $stockLocations = LocationInventory::where('gci_part_id', $p->gci_part_id)
                ->where('qty_on_hand', '>', 0)
                ->orderByDesc('qty_on_hand')
                ->get()
                ->map(fn($loc) => [
                    'location_code' => $loc->location_code,
                    'qty' => (float) $loc->qty_on_hand,
                    'batch_no' => $loc->batch_no,
                ])->toArray();

            return [
                'pick_id' => $p->id,
                'gci_part_id' => $p->gci_part_id,
                'part_no' => $p->part?->part_no,
                'part_name' => $p->part?->part_name,
                'barcode' => $p->part?->barcode,
                'model' => $p->part?->model,
                'expected_location' => $p->part?->default_location,
                'qty_plan' => (int) $p->qty_plan,
                'qty_picked' => (int) $p->qty_picked,
                'qty_remaining' => $p->qty_remaining,
                'status' => $p->status,
                'progress' => $p->progress_percent,
                'pick_location' => $p->pick_location,
                'po_no' => $p->outgoingPoItem?->outgoingPo?->po_no,
                'stock_locations' => $stockLocations,
            ];
        });

        $totalPlan = $picks->sum('qty_plan');
        $totalPicked = $picks->sum('qty_picked');

        return response()->json([
            'success' => true,
            'delivery_order' => [
                'id' => $do->id,
                'do_no' => $do->do_no,
                'trip_no' => $do->trip_no,
                'do_date' => $do->do_date?->format('Y-m-d'),
                'status' => $do->status,
                'customer' => [
                    'id' => $do->customer?->id,
                    'code' => $do->customer?->code,
                    'name' => $do->customer?->name,
                ],
                'qty_plan' => (int) $totalPlan,
                'qty_picked' => (int) $totalPicked,
                'progress' => $totalPlan > 0 ? round(($totalPicked / $totalPlan) * 100, 1) : 0,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Step 3: Scan location — validate location and return parts in this DO that have stock there.
     * POST /api/picking-fg/scan-location
     * Body: { delivery_order_id, date, location_code }
     */
    public function scanLocation(Request $request)
    {
        $request->validate([
            'delivery_order_id' => 'required|integer|exists:delivery_orders,id',
            'date' => 'required|date',
            'location_code' => 'required|string|max:100',
        ]);

        $locationCode = $this->parseLocationCode($request->location_code);

        // Validate location exists
        $location = WarehouseLocation::where('location_code', $locationCode)->first();
        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => "Location {$locationCode} not found.",
            ], 404);
        }

        // Get pending picks for this DO
        $picks = OutgoingPickingFg::with('part')
            ->where('delivery_order_id', $request->delivery_order_id)
            ->where('delivery_date', $request->date)
            ->where('status', '!=', 'completed')
            ->get();

        if ($picks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending picks for this DO.',
            ], 404);
        }

        // Find which parts have stock at this location
        $gciPartIds = $picks->pluck('gci_part_id')->unique()->toArray();
        $stockAtLocation = LocationInventory::where('location_code', $locationCode)
            ->whereIn('gci_part_id', $gciPartIds)
            ->where('qty_on_hand', '>', 0)
            ->get()
            ->keyBy('gci_part_id');

        $partsAtLocation = $picks->filter(function ($p) use ($stockAtLocation) {
            return $stockAtLocation->has($p->gci_part_id);
        })->map(function ($p) use ($stockAtLocation, $locationCode) {
            $stock = $stockAtLocation->get($p->gci_part_id);
            return [
                'pick_id' => $p->id,
                'gci_part_id' => $p->gci_part_id,
                'part_no' => $p->part?->part_no,
                'part_name' => $p->part?->part_name,
                'barcode' => $p->part?->barcode,
                'qty_plan' => (int) $p->qty_plan,
                'qty_picked' => (int) $p->qty_picked,
                'qty_remaining' => $p->qty_remaining,
                'stock_at_location' => (float) $stock->qty_on_hand,
                'batch_no' => $stock->batch_no,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'location' => [
                'code' => $locationCode,
                'zone' => $location->zone,
                'class' => $location->class,
            ],
            'parts_count' => $partsAtLocation->count(),
            'parts' => $partsAtLocation,
        ]);
    }

    /**
     * Step 4: Scan part — validate part barcode/part_no against DO and location.
     * POST /api/picking-fg/scan-part
     * Body: { delivery_order_id, date, location_code, part_code (barcode or part_no) }
     */
    public function scanPart(Request $request)
    {
        $request->validate([
            'delivery_order_id' => 'required|integer|exists:delivery_orders,id',
            'date' => 'required|date',
            'location_code' => 'required|string|max:100',
            'part_code' => 'required|string|max:255',
        ]);

        $locationCode = $this->parseLocationCode($request->location_code);
        $partCode = strtoupper(trim($request->part_code));

        // Find part by barcode or part_no
        $part = GciPart::where('barcode', $partCode)
            ->orWhere('part_no', $partCode)
            ->first();

        if (!$part) {
            return response()->json([
                'success' => false,
                'message' => "Part not found: {$partCode}",
            ], 404);
        }

        // Check if this part has a pending pick in this DO
        $pick = OutgoingPickingFg::where('delivery_order_id', $request->delivery_order_id)
            ->where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id)
            ->where('status', '!=', 'completed')
            ->first();

        if (!$pick) {
            return response()->json([
                'success' => false,
                'message' => "Part {$part->part_no} is not in this DO or already completed.",
            ], 422);
        }

        // Check stock at scanned location
        $stock = LocationInventory::where('gci_part_id', $part->id)
            ->where('location_code', $locationCode)
            ->where('qty_on_hand', '>', 0)
            ->first();

        if (!$stock) {
            // Get alternative locations where this part has stock
            $altLocations = LocationInventory::where('gci_part_id', $part->id)
                ->where('qty_on_hand', '>', 0)
                ->orderByDesc('qty_on_hand')
                ->limit(5)
                ->get()
                ->map(fn($loc) => [
                    'location_code' => $loc->location_code,
                    'qty' => (float) $loc->qty_on_hand,
                    'batch_no' => $loc->batch_no,
                ])->toArray();

            return response()->json([
                'success' => false,
                'message' => "No stock for {$part->part_no} at {$locationCode}.",
                'alternative_locations' => $altLocations,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'part' => [
                'id' => $part->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'barcode' => $part->barcode,
                'model' => $part->model,
            ],
            'pick' => [
                'id' => $pick->id,
                'qty_plan' => (int) $pick->qty_plan,
                'qty_picked' => (int) $pick->qty_picked,
                'qty_remaining' => $pick->qty_remaining,
                'status' => $pick->status,
            ],
            'stock' => [
                'location_code' => $locationCode,
                'qty_available' => (float) $stock->qty_on_hand,
                'batch_no' => $stock->batch_no,
            ],
            'max_pick' => min($pick->qty_remaining, (int) $stock->qty_on_hand),
        ]);
    }

    /**
     * Step 5: Submit pick quantity.
     * POST /api/picking-fg/pick
     * Body: { delivery_order_id, date, part_no, qty, location, batch_no? }
     */
    public function updatePick(Request $request)
    {
        $request->validate([
            'delivery_order_id' => 'required|integer|exists:delivery_orders,id',
            'date' => 'required|date',
            'part_no' => 'required|string',
            'qty' => 'required|integer|min:1',
            'location' => 'required|string|max:100',
            'batch_no' => 'nullable|string|max:255',
        ]);

        $partNo = strtoupper(trim((string) $request->part_no));
        $part = GciPart::where('part_no', $partNo)
            ->orWhere('barcode', $partNo)
            ->first();

        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        $locationCode = $this->parseLocationCode($request->location);

        // Validate stock exists at this location
        $stockCheck = LocationInventory::where('gci_part_id', $part->id)
            ->where('location_code', $locationCode)
            ->where('qty_on_hand', '>', 0)
            ->first();

        if (!$stockCheck) {
            return response()->json([
                'success' => false,
                'message' => "No stock for {$part->part_no} at {$locationCode}.",
            ], 422);
        }

        $result = DB::transaction(function () use ($request, $part, $locationCode) {
            $pick = OutgoingPickingFg::where('delivery_order_id', $request->delivery_order_id)
                ->where('delivery_date', $request->date)
                ->where('gci_part_id', $part->id)
                ->lockForUpdate()
                ->first();

            if (!$pick) {
                return response()->json(['success' => false, 'message' => 'Picking record not found'], 404);
            }

            $remainingBefore = max(0, (int) $pick->qty_plan - (int) $pick->qty_picked);
            if ($remainingBefore <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This picking task is already completed.',
                ], 422);
            }

            $requestedQty = (int) request('qty');
            $appliedQty = min($requestedQty, $remainingBefore);
            $newQty = (int) $pick->qty_picked + $appliedQty;

            $pick->update([
                'qty_picked' => $newQty,
                'status' => ($newQty >= (int) $pick->qty_plan) ? 'completed' : 'picking',
                'pick_location' => $locationCode,
                'picked_by' => Auth::id(),
                'picked_at' => now(),
            ]);

            // Decrement warehouse stock (FIFO across batches at location)
            $batchNo = request('batch_no') ? strtoupper(trim(request('batch_no'))) : null;
            LocationInventory::consumeStock(
                null,
                $locationCode,
                $appliedQty,
                $batchNo,
                $part->id,
                'PICKING',
                'DO#' . ($pick->deliveryOrder?->do_no ?? 'N/A')
            );

            $doCompleted = false;

            if ($pick->delivery_order_id) {
                $pendingCount = OutgoingPickingFg::where('delivery_order_id', $pick->delivery_order_id)
                    ->where('status', '!=', 'completed')
                    ->count();

                if ($pendingCount === 0) {
                    DeliveryOrder::where('id', $pick->delivery_order_id)->update(['status' => 'completed']);

                    // Auto-create Delivery Note
                    $do = DeliveryOrder::find($pick->delivery_order_id);
                    $service = app(\App\Services\DeliveryOutgoingService::class);
                    $service->createDeliveryNote(
                        [$do->id],
                        $do->customer_id,
                        null,
                        null,
                        [
                            'delivery_date' => $do->delivery_date ?? now()->toDateString(),
                            'notes' => 'Auto-generated from mobile picking',
                            'created_by' => Auth::id(),
                        ]
                    );

                    $doCompleted = true;
                } else {
                    DeliveryOrder::where('id', $pick->delivery_order_id)
                        ->where('status', 'completed')
                        ->update(['status' => 'picking']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $doCompleted
                    ? 'Pick updated. DO completed — Delivery Note created!'
                    : 'Pick updated',
                'data' => [
                    'id' => $pick->id,
                    'part_no' => $part->part_no,
                    'part_name' => $part->part_name,
                    'qty_picked' => (int) $pick->qty_picked,
                    'qty_plan' => (int) $pick->qty_plan,
                    'qty_remaining' => $pick->qty_remaining,
                    'status' => $pick->status,
                    'progress' => $pick->progress_percent,
                    'location' => $locationCode,
                    'do_no' => $pick->deliveryOrder?->do_no,
                    'requested_qty' => $requestedQty,
                    'applied_qty' => $appliedQty,
                    'rejected_qty' => max(0, $requestedQty - $appliedQty),
                    'do_completed' => $doCompleted,
                ],
            ]);
        });

        return $result;
    }

    /**
     * Parse location code from raw input (may be JSON QR payload or plain text).
     */
    private function parseLocationCode(?string $raw): string
    {
        $raw = trim((string) $raw);
        if (str_starts_with($raw, '{')) {
            $json = json_decode($raw, true);
            if (is_array($json) && isset($json['location_code'])) {
                return strtoupper(trim($json['location_code']));
            }
        }
        return strtoupper($raw);
    }
}