<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransfer;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryBinTransfer;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;

class InventoryTransferController extends Controller
{
    use LogsActivity;

    /**
     * Display transfer history
     */
    public function index(Request $request)
    {
        $transfers = InventoryBinTransfer::with(['gciPart', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('inventory.transfers.index', compact('transfers'));
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        $parts = GciPart::whereHas('inventoryLocationStocks', function ($q) {
            $q->where('qty_on_hand', '>', 0);
        })->with('inventoryLocationStocks')->get();

        $gciParts = GciPart::orderBy('part_no')->get();

        return view('inventory.transfers.create', compact('parts', 'gciParts'));
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'gci_part_id' => ['required', 'exists:gci_parts,id'],
            'from_location_code' => ['required', 'string', 'max:50'],
            'to_location_code' => ['required', 'string', 'max:50'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $gciPartId = (int) $validated['gci_part_id'];
                $fromLocation = strtoupper(trim($validated['from_location_code']));
                $toLocation = strtoupper(trim($validated['to_location_code']));
                $qty = (float) $validated['qty'];
                $batchNo = (string) ($validated['batch_no'] ?? '');

                $available = InventoryLocationStock::getStockByLocation($gciPartId, $fromLocation, $batchNo);
                if ($available < $qty) {
                    throw new \Exception('Insufficient inventory at source location. Available: ' . $available);
                }

                InventoryLocationStock::consumeStock(
                    gciPartId: $gciPartId,
                    locationCode: $fromLocation,
                    qty: $qty,
                    batchNo: $batchNo,
                    transactionType: 'TRANSFER',
                    sourceReference: 'BIN-TRANSFER',
                    createdBy: Auth::id()
                );

                InventoryLocationStock::updateStock(
                    gciPartId: $gciPartId,
                    locationCode: $toLocation,
                    qtyChange: $qty,
                    batchNo: $batchNo,
                    tag: null,
                    transactionType: 'TRANSFER',
                    sourceReference: 'BIN-TRANSFER',
                    sourceReceiveId: null,
                    sourceArrivalId: null,
                    sourceInvoiceNo: null,
                    sourceDeliveryNoteNo: null,
                    weightKgm: null,
                    createdBy: Auth::id()
                );

                InventoryBinTransfer::create([
                    'transfer_no' => $this->generateTransferNo(),
                    'gci_part_id' => $gciPartId,
                    'from_location_code' => $fromLocation,
                    'to_location_code' => $toLocation,
                    'batch_no' => $batchNo,
                    'qty_transferred' => $qty,
                    'status' => 'completed',
                    'transfer_type' => 'manual',
                    'created_by' => Auth::id(),
                    'completed_by' => Auth::id(),
                    'completed_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                InventoryTransfer::create([
                    'gci_part_id' => $gciPartId,
                    'qty' => $qty,
                    'transfer_type' => 'manual',
                    'created_by' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            $this->logActivity('STORE InventoryTransfer', "gci_part_id:{$validated['gci_part_id']} -> {$validated['to_location_code']}", [
                'qty' => $validated['qty'],
            ]);

            return redirect()
                ->route('inventory.transfers.index')
                ->with('success', 'Inventory transferred successfully.');
        } catch (\Exception $e) {
            $this->logActivityError('STORE InventoryTransfer FAILED', $e->getMessage(), [
                'gci_part_id' => $validated['gci_part_id'],
                'qty' => $validated['qty'],
            ]);
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Auto-sync inventory based on part_no matching
     */
    public function autoSync(Request $request)
    {
        $validated = $request->validate([
            'min_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $minQty = $validated['min_qty'] ?? 0;
        $transferred = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($minQty, &$transferred, &$errors) {
                $parts = GciPart::whereHas('inventoryLocationStocks', function ($q) use ($minQty) {
                    $q->where('qty_on_hand', '>', $minQty);
                })->with('inventoryLocationStocks')->get();

                foreach ($parts as $part) {
                    foreach ($part->inventoryLocationStocks as $stock) {
                        $qty = (float) $stock->qty_on_hand;
                        if ($qty <= $minQty) {
                            continue;
                        }

                        try {
                            InventoryLocationStock::updateStock(
                                gciPartId: (int) $part->id,
                                locationCode: 'AA-BULK',
                                qtyChange: $qty,
                                batchNo: $stock->batch_no,
                                tag: null,
                                transactionType: 'AUTO_SYNC',
                                sourceReference: 'AUTO-SYNC',
                                sourceReceiveId: null,
                                sourceArrivalId: null,
                                sourceInvoiceNo: null,
                                sourceDeliveryNoteNo: null,
                                weightKgm: null,
                                createdBy: Auth::id()
                            );

                            InventoryBinTransfer::create([
                                'transfer_no' => $this->generateTransferNo(),
                                'gci_part_id' => $part->id,
                                'from_location_code' => $stock->location_code,
                                'to_location_code' => 'AA-BULK',
                                'batch_no' => $stock->batch_no,
                                'qty_transferred' => $qty,
                                'status' => 'completed',
                                'transfer_type' => 'auto',
                                'created_by' => Auth::id(),
                                'completed_by' => Auth::id(),
                                'completed_at' => now(),
                                'notes' => 'Auto-sync based on part_no matching',
                            ]);

                            InventoryTransfer::create([
                                'gci_part_id' => $part->id,
                                'qty' => $qty,
                                'transfer_type' => 'auto',
                                'created_by' => Auth::id(),
                                'notes' => 'Auto-sync based on part_no matching',
                            ]);

                            $transferred++;
                        } catch (\Exception $e) {
                            $errors[] = "Failed to transfer {$part->part_no}: {$e->getMessage()}";
                        }
                    }
                }
            });

            $message = "Auto-sync completed. Transferred {$transferred} location records.";
            if (!empty($errors)) {
                $message .= ' Errors: ' . implode(', ', $errors);
            }

            $this->logActivity('AUTO-SYNC InventoryTransfer', "transferred:{$transferred}", [
                'errors_count' => count($errors),
            ]);

            return redirect()
                ->route('inventory.transfers.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            $this->logActivityError('AUTO-SYNC InventoryTransfer FAILED', $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function generateTransferNo(): string
    {
        $date = now()->format('Ymd');
        $last = InventoryBinTransfer::whereDate('created_at', today())->count();
        return 'TRF-' . $date . '-' . str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}
