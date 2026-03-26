<?php

namespace App\Http\Controllers;

use App\Models\BinTransfer;
use App\Models\GciPart;
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
    private function currentMode(Request $request): string
    {
        $mode = (string) ($request->route('mode') ?? 'bin_to_bin');
        return in_array($mode, ['bin_to_bin', 'batch_to_batch'], true) ? $mode : 'bin_to_bin';
    }

    private function modeMeta(string $mode): array
    {
        return $mode === 'batch_to_batch'
            ? [
                'title' => 'Batch to Batch',
                'history_title' => 'Batch to Batch History',
                'create_title' => 'Transfer Material Between Batches',
                'description' => 'Move material from one batch to another batch within a location.',
            ]
            : [
                'title' => 'Bin to Bin',
                'history_title' => 'Bin to Bin History',
                'create_title' => 'Transfer Material Between Bins',
                'description' => 'Move material from one warehouse location to another.',
            ];
    }

    public function index(Request $request)
    {
        $mode = $this->currentMode($request);
        $query = BinTransfer::with(['part', 'gciPart', 'fromLocation', 'toLocation', 'creator'])
            ->when($mode === 'bin_to_bin', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('transfer_type')->orWhere('transfer_type', 'bin_to_bin');
                });
            })
            ->when($mode === 'batch_to_batch', fn ($q) => $q->where('transfer_type', 'batch_to_batch'));

        if ($request->filled('part_id')) {
            $pid = $request->part_id;
            $query->where(function ($q) use ($pid) {
                $q->where('part_id', $pid)->orWhere('gci_part_id', $pid);
            });
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
            ->paginate(50)
            ->withQueryString();

        $parts = Part::orderBy('part_no')->get();
        $locations = WarehouseLocation::where('status', 'ACTIVE')->orderBy('location_code')->get();
        $meta = $this->modeMeta($mode);

        return view('warehouse.bin-transfers.index', compact('transfers', 'parts', 'locations', 'mode', 'meta'));
    }

    public function create(Request $request)
    {
        $mode = $this->currentMode($request);
        $locations = WarehouseLocation::where('status', 'ACTIVE')->orderBy('location_code')->get();
        $meta = $this->modeMeta($mode);

        return view('warehouse.bin-transfers.create', compact('locations', 'mode', 'meta'));
    }

    public function store(Request $request)
    {
        $mode = $this->currentMode($request);

        $rules = [
            'part_id' => ['required'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($mode === 'batch_to_batch') {
            $rules['location_code'] = [
                'required',
                'string',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ];
            $rules['from_batch_no'] = ['required', 'string', 'max:255'];
            $rules['to_batch_no'] = ['required', 'string', 'max:255', 'different:from_batch_no'];
        } else {
            $rules['from_location_code'] = [
                'required',
                'string',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ];
            $rules['to_location_code'] = [
                'required',
                'string',
                'different:from_location_code',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ];
        }

        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($validated, $mode) {
                $partId = (int) $validated['part_id'];
                $qty = (float) $validated['qty'];
                $part = Part::find($partId);
                $gciPartId = $part?->gci_part_id ?: GciPart::where('id', $partId)->value('id');
                $resolvedPartId = $part ? $partId : null;
                $resolvedGciPartId = $gciPartId ?: null;

                if (!$resolvedPartId && !$resolvedGciPartId) {
                    throw new \Exception("Part ID {$partId} not found in master list.");
                }

                if ($mode === 'batch_to_batch') {
                    $locationCode = strtoupper(trim((string) $validated['location_code']));
                    $fromBatch = strtoupper(trim((string) $validated['from_batch_no']));
                    $toBatch = strtoupper(trim((string) $validated['to_batch_no']));

                    $sourceStock = LocationInventory::getStockByLocation($partId, $locationCode, $fromBatch, $resolvedGciPartId);
                    if ($sourceStock < $qty) {
                        throw new \Exception("Insufficient stock at {$locationCode} batch {$fromBatch}. Available: {$sourceStock}, Requested: {$qty}");
                    }

                    LocationInventory::updateStock($resolvedPartId, $locationCode, -$qty, $fromBatch, null, $resolvedGciPartId, 'TRANSFER', "BATCH:{$fromBatch}->{$toBatch}");
                    LocationInventory::updateStock($resolvedPartId, $locationCode, $qty, $toBatch, null, $resolvedGciPartId, 'TRANSFER', "BATCH:{$fromBatch}->{$toBatch}");

                    $transfer = BinTransfer::create([
                        'part_id' => $resolvedPartId,
                        'gci_part_id' => $resolvedGciPartId,
                        'transfer_type' => 'batch_to_batch',
                        'from_location_code' => $locationCode,
                        'to_location_code' => $locationCode,
                        'from_batch_no' => $fromBatch,
                        'to_batch_no' => $toBatch,
                        'qty' => $qty,
                        'transfer_date' => $validated['transfer_date'],
                        'created_by' => Auth::id(),
                        'notes' => $validated['notes'] ?? null,
                        'status' => 'completed',
                    ]);

                    $payload = [
                        'part_id' => $resolvedPartId,
                        'gci_part_id' => $resolvedGciPartId,
                        'location_code' => $locationCode,
                        'batch_no' => $fromBatch,
                        'qty_before' => (float) $sourceStock,
                        'qty_after' => (float) ($sourceStock - $qty),
                        'qty_change' => (float) (0 - $qty),
                        'reason' => trim('Batch to Batch ' . $fromBatch . ' -> ' . $toBatch . ($validated['notes'] ? (' | ' . $validated['notes']) : '')),
                        'adjusted_at' => $transfer->transfer_date ? \Illuminate\Support\Carbon::parse($transfer->transfer_date) : now(),
                        'created_by' => Auth::id(),
                    ];

                    if (Schema::hasColumn('location_inventory_adjustments', 'from_location_code')) {
                        $payload['from_location_code'] = $locationCode;
                    }
                    if (Schema::hasColumn('location_inventory_adjustments', 'to_location_code')) {
                        $payload['to_location_code'] = $locationCode;
                    }
                    if (Schema::hasColumn('location_inventory_adjustments', 'from_batch_no')) {
                        $payload['from_batch_no'] = $fromBatch;
                    }
                    if (Schema::hasColumn('location_inventory_adjustments', 'to_batch_no')) {
                        $payload['to_batch_no'] = $toBatch;
                    }
                    if (Schema::hasColumn('location_inventory_adjustments', 'action_type')) {
                        $payload['action_type'] = 'transfer';
                    }

                    LocationInventoryAdjustment::query()->create($payload);
                    return;
                }

                $fromLocation = strtoupper(trim((string) $validated['from_location_code']));
                $toLocation = strtoupper(trim((string) $validated['to_location_code']));
                $sourceStock = LocationInventory::getStockByLocation($partId, $fromLocation, null, $resolvedGciPartId);

                if ($sourceStock < $qty) {
                    throw new \Exception("Insufficient stock at {$fromLocation}. Available: {$sourceStock}, Requested: {$qty}");
                }

                LocationInventory::updateStock($resolvedPartId, $fromLocation, -$qty, null, null, $resolvedGciPartId, 'TRANSFER', "BIN:{$fromLocation}->{$toLocation}");
                LocationInventory::updateStock($resolvedPartId, $toLocation, $qty, null, null, $resolvedGciPartId, 'TRANSFER', "BIN:{$fromLocation}->{$toLocation}");

                $transfer = BinTransfer::create([
                    'part_id' => $resolvedPartId,
                    'gci_part_id' => $resolvedGciPartId,
                    'transfer_type' => 'bin_to_bin',
                    'from_location_code' => $fromLocation,
                    'to_location_code' => $toLocation,
                    'qty' => $qty,
                    'transfer_date' => $validated['transfer_date'],
                    'created_by' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'completed',
                ]);

                $payload = [
                    'part_id' => $resolvedPartId,
                    'gci_part_id' => $resolvedGciPartId,
                    'location_code' => $fromLocation,
                    'batch_no' => null,
                    'qty_before' => (float) $sourceStock,
                    'qty_after' => (float) ($sourceStock - $qty),
                    'qty_change' => (float) (0 - $qty),
                    'reason' => trim('Bin to Bin ' . $fromLocation . ' -> ' . $toLocation . ($validated['notes'] ? (' | ' . $validated['notes']) : '')),
                    'adjusted_at' => $transfer->transfer_date ? \Illuminate\Support\Carbon::parse($transfer->transfer_date) : now(),
                    'created_by' => Auth::id(),
                ];

                if (Schema::hasColumn('location_inventory_adjustments', 'from_location_code')) {
                    $payload['from_location_code'] = $fromLocation;
                }
                if (Schema::hasColumn('location_inventory_adjustments', 'to_location_code')) {
                    $payload['to_location_code'] = $toLocation;
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

            $route = $mode === 'batch_to_batch' ? 'warehouse.batch-transfers.index' : 'warehouse.bin-transfers.index';
            $message = $mode === 'batch_to_batch'
                ? 'Batch to Batch completed successfully.'
                : 'Bin to Bin completed successfully.';

            return redirect()->route($route)->with('success', $message);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(BinTransfer $binTransfer)
    {
        $binTransfer->load(['part', 'gciPart', 'fromLocation', 'toLocation', 'creator']);

        $currentFromStock = LocationInventory::getStockByLocation(
            $binTransfer->part_id ?: $binTransfer->gci_part_id,
            $binTransfer->from_location_code,
            $binTransfer->from_batch_no,
            $binTransfer->gci_part_id
        );

        $currentToStock = LocationInventory::getStockByLocation(
            $binTransfer->part_id ?: $binTransfer->gci_part_id,
            $binTransfer->to_location_code,
            $binTransfer->to_batch_no,
            $binTransfer->gci_part_id
        );

        $mode = $binTransfer->transfer_type === 'batch_to_batch' ? 'batch_to_batch' : 'bin_to_bin';
        $meta = $this->modeMeta($mode);

        return view('warehouse.bin-transfers.show', compact('binTransfer', 'currentFromStock', 'currentToStock', 'mode', 'meta'));
    }

    public function getLocationStock(Request $request)
    {
        $request->validate([
            'part_id' => ['required'],
            'location_code' => ['required', 'string'],
            'batch_no' => ['nullable', 'string'],
        ]);

        $stock = LocationInventory::getStockByLocation(
            (int) $request->part_id,
            (string) $request->location_code,
            $request->query('batch_no')
        );

        return response()->json([
            'success' => true,
            'stock' => $stock,
            'formatted' => formatNumber($stock),
        ]);
    }

    public function getPartLocations(Request $request)
    {
        $request->validate([
            'part_id' => ['required'],
        ]);

        $locations = LocationInventory::getLocationsForPart((int) $request->part_id);

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

    public function getLocationBatches(Request $request)
    {
        $request->validate([
            'part_id' => ['required'],
            'location_code' => ['required', 'string'],
        ]);

        $partId = (int) $request->part_id;
        $locationCode = strtoupper(trim((string) $request->location_code));
        $part = Part::find($partId);
        $gciPartId = $part?->gci_part_id ?: GciPart::where('id', $partId)->value('id');

        $rows = LocationInventory::query()
            ->where('location_code', $locationCode)
            ->where('qty_on_hand', '>', 0)
            ->when($gciPartId, fn ($q) => $q->where('gci_part_id', $gciPartId))
            ->when(!$gciPartId && $part, fn ($q) => $q->where('part_id', $partId))
            ->orderBy('production_date')
            ->orderBy('batch_no')
            ->get(['batch_no', 'qty_on_hand', 'production_date']);

        return response()->json([
            'success' => true,
            'batches' => $rows,
        ]);
    }

    public function printLabel(BinTransfer $binTransfer)
    {
        $binTransfer->load(['part', 'gciPart', 'fromLocation', 'toLocation', 'creator']);

        $warehouseLocation = WarehouseLocation::where('location_code', $binTransfer->to_location_code)->first();
        $mode = $binTransfer->transfer_type === 'batch_to_batch' ? 'batch_to_batch' : 'bin_to_bin';

        $payload = [
            'type' => $mode === 'batch_to_batch' ? 'BATCH_TRANSFER_LABEL' : 'BIN_TRANSFER_LABEL',
            'transfer_id' => $binTransfer->id,
            'part_no' => (string) ($binTransfer->gciPart->part_no ?? ($binTransfer->part->part_no ?? '')),
            'part_name' => (string) ($binTransfer->gciPart->part_name ?? ($binTransfer->part->part_name_gci ?? '')),
            'from_location' => (string) $binTransfer->from_location_code,
            'to_location' => (string) $binTransfer->to_location_code,
            'from_batch_no' => (string) ($binTransfer->from_batch_no ?? ''),
            'to_batch_no' => (string) ($binTransfer->to_batch_no ?? ''),
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
        $meta = $this->modeMeta($mode);

        return view('warehouse.bin-transfers.label', compact('binTransfer', 'qrSvg', 'warehouseLocation', 'mode', 'meta'));
    }
}
