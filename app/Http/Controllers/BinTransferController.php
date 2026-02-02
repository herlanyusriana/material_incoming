<?php

namespace App\Http\Controllers;

use App\Models\BinTransfer;
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
use App\Models\Part;
use App\Models\WarehouseLocation;
use App\Support\QrSvg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BinTransferController extends Controller
{
    /**
     * Display transfer history
     */
    public function index(Request $request)
    {
        $query = BinTransfer::with(['part', 'fromLocation', 'toLocation', 'creator']);

        // Filters
        if ($request->filled('part_id')) {
            $query->where('part_id', $request->part_id);
        }

        if ($request->filled('location')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_location_code', $request->location)
                    ->orWhere('to_location_code', $request->location);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transfer_date', '<=', $request->date_to);
        }

        $transfers = $query->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // For filters
        $parts = Part::orderBy('part_no')->get();
        $locations = WarehouseLocation::where('status', 'ACTIVE')->orderBy('location_code')->get();

        return view('warehouse.bin-transfers.index', compact('transfers', 'parts', 'locations'));
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        // Get parts that have stock in any location
        $parts = Part::whereHas('locationInventory', function ($q) {
            $q->where('qty_on_hand', '>', 0);
        })->orderBy('part_no')->get();

        // Get active warehouse locations
        $locations = WarehouseLocation::where('status', 'ACTIVE')
            ->orderBy('location_code')
            ->get();

        return view('warehouse.bin-transfers.create', compact('parts', 'locations'));
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'from_location_code' => [
                'required',
                'string',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'to_location_code' => [
                'required',
                'string',
                'different:from_location_code',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $partId = $validated['part_id'];
                $fromLocation = $validated['from_location_code'];
                $toLocation = $validated['to_location_code'];
                $qty = (float) $validated['qty'];

                // 1. Check source location has sufficient stock
                $sourceStock = LocationInventory::getStockByLocation($partId, $fromLocation);
                if ($sourceStock < $qty) {
                    throw new \Exception("Insufficient stock at {$fromLocation}. Available: {$sourceStock}, Requested: {$qty}");
                }

                // 2. Decrement source location
                LocationInventory::updateStock($partId, $fromLocation, -$qty);

                // 3. Increment target location
                LocationInventory::updateStock($partId, $toLocation, $qty);

                // 4. Log transfer
                $transfer = BinTransfer::create([
                    'part_id' => $partId,
                    'from_location_code' => $fromLocation,
                    'to_location_code' => $toLocation,
                    'qty' => $qty,
                    'transfer_date' => $validated['transfer_date'],
                    'created_by' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                ]);

                // 5. Mirror to Adjustment History so movement is visible in one place.
                $payload = [
                    'part_id' => $partId,
                    'location_code' => strtoupper(trim((string) $fromLocation)),
                    'batch_no' => null,
                    // Keep qty_before/after referencing the FROM side for consistency with existing UI.
                    'qty_before' => (float) $sourceStock,
                    'qty_after' => (float) ($sourceStock - $qty),
                    'qty_change' => (float) (0 - $qty),
                    'reason' => trim('Bin transfer ' . $fromLocation . ' → ' . $toLocation . ($validated['notes'] ? (' | ' . $validated['notes']) : '')),
                    'adjusted_at' => $transfer->transfer_date ? \Illuminate\Support\Carbon::parse($transfer->transfer_date) : now(),
                    'created_by' => Auth::id(),
                ];

                if (Schema::hasColumn('location_inventory_adjustments', 'from_location_code')) {
                    $payload['from_location_code'] = strtoupper(trim((string) $fromLocation));
                }
                if (Schema::hasColumn('location_inventory_adjustments', 'to_location_code')) {
                    $payload['to_location_code'] = strtoupper(trim((string) $toLocation));
                }
                if (Schema::hasColumn('location_inventory_adjustments', 'from_batch_no')) {
                    $payload['from_batch_no'] = null;
                }
                if (Schema::hasColumn('location_inventory_adjustments', 'to_batch_no')) {
                    $payload['to_batch_no'] = null;
                }
                if (Schema::hasColumn('location_inventory_adjustments', 'action_type')) {
                    $payload['action_type'] = 'transfer';
                }

                LocationInventoryAdjustment::query()->create($payload);
            });

            return redirect()
                ->route('warehouse.bin-transfers.index')
                ->with('success', 'Bin transfer completed successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified transfer
     */
    public function show(BinTransfer $binTransfer)
    {
        $binTransfer->load(['part', 'fromLocation', 'toLocation', 'creator']);

        // Get current stock at both locations
        $currentFromStock = LocationInventory::getStockByLocation(
            $binTransfer->part_id,
            $binTransfer->from_location_code
        );

        $currentToStock = LocationInventory::getStockByLocation(
            $binTransfer->part_id,
            $binTransfer->to_location_code
        );

        return view('warehouse.bin-transfers.show', compact('binTransfer', 'currentFromStock', 'currentToStock'));
    }

    /**
     * AJAX endpoint: Get stock for a part at a specific location
     */
    public function getLocationStock(Request $request)
    {
        $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'location_code' => ['required', 'string'],
        ]);

        $stock = LocationInventory::getStockByLocation(
            $request->part_id,
            $request->location_code
        );

        return response()->json([
            'success' => true,
            'stock' => $stock,
            'formatted' => formatNumber($stock),
        ]);
    }

    /**
     * AJAX endpoint: Get all locations with stock for a part
     */
    public function getPartLocations(Request $request)
    {
        $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
        ]);

        $locations = LocationInventory::getLocationsForPart($request->part_id);

        return response()->json([
            'success' => true,
            'locations' => $locations->map(function ($loc) {
                return [
                    'location_code' => $loc->location_code,
                    'qty_on_hand' => $loc->qty_on_hand,
                    'formatted_qty' => formatNumber($loc->qty_on_hand),
                ];
            }),
        ]);
    }

    /**
     * Print label for bin transfer
     */
    public function printLabel(BinTransfer $binTransfer)
    {
        $binTransfer->load(['part', 'fromLocation', 'toLocation', 'creator']);

        $warehouseLocation = WarehouseLocation::where('location_code', $binTransfer->to_location_code)->first();

        // Build QR code payload
        $payload = [
            'type' => 'BIN_TRANSFER_LABEL',
            'transfer_id' => $binTransfer->id,
            'part_no' => (string) ($binTransfer->part->part_no ?? ''),
            'part_name' => (string) ($binTransfer->part->part_name_gci ?? ''),
            'from_location' => (string) $binTransfer->from_location_code,
            'to_location' => (string) $binTransfer->to_location_code,
            'warehouse' => [
                'location_code' => (string) ($warehouseLocation?->location_code ?? ''),
                'class' => (string) ($warehouseLocation?->class ?? ''),
                'zone' => (string) ($warehouseLocation?->zone ?? ''),
            ],
            'qty' => (float) $binTransfer->qty,
            'transfer_date' => $binTransfer->transfer_date->format('Y-m-d'),
            'transferred_by' => (string) ($binTransfer->creator->name ?? ''),
        ];

        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';

        $qrSvg = QrSvg::make($payloadString, 160, 0);

        return view('warehouse.bin-transfers.label', compact('binTransfer', 'qrSvg', 'warehouseLocation'));
    }
}
