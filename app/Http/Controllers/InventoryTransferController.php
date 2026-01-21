<?php

namespace App\Http\Controllers;

use App\Models\GciInventory;
use App\Models\GciPart;
use App\Models\Inventory;
use App\Models\InventoryTransfer;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryTransferController extends Controller
{
    /**
     * Display transfer history
     */
    public function index(Request $request)
    {
        $transfers = InventoryTransfer::with(['part', 'gciPart', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('inventory.transfers.index', compact('transfers'));
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        // Get parts that have inventory
        $parts = Part::whereHas('inventory', function ($q) {
            $q->where('on_hand', '>', 0);
        })->with('inventory')->get();

        // Get all GciParts for mapping
        $gciParts = GciPart::orderBy('part_no')->get();

        return view('inventory.transfers.create', compact('parts', 'gciParts'));
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'gci_part_id' => ['required', 'exists:gci_parts,id'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $partId = $validated['part_id'];
                $gciPartId = $validated['gci_part_id'];
                $qty = (float) $validated['qty'];

                // 1. Check source inventory
                $sourceInv = Inventory::where('part_id', $partId)->lockForUpdate()->first();
                if (!$sourceInv || $sourceInv->on_hand < $qty) {
                    throw new \Exception('Insufficient inventory in logistics. Available: ' . ($sourceInv->on_hand ?? 0));
                }

                // 2. Decrement source
                $sourceInv->decrement('on_hand', $qty);

                // 3. Increment target
                $targetInv = GciInventory::firstOrCreate(
                    ['gci_part_id' => $gciPartId],
                    ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()]
                );
                $targetInv->increment('on_hand', $qty);
                $targetInv->update(['as_of_date' => now()]);

                // 4. Log transfer
                InventoryTransfer::create([
                    'part_id' => $partId,
                    'gci_part_id' => $gciPartId,
                    'qty' => $qty,
                    'transfer_type' => 'manual',
                    'created_by' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            return redirect()
                ->route('inventory.transfers.index')
                ->with('success', 'Inventory transferred successfully.');
        } catch (\Exception $e) {
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
                // Find parts with matching part_no
                $parts = Part::whereHas('inventory', function ($q) use ($minQty) {
                    $q->where('on_hand', '>', $minQty);
                })->with('inventory')->get();

                foreach ($parts as $part) {
                    $gciPart = GciPart::where('part_no', $part->part_no)->first();

                    if (!$gciPart) {
                        continue; // Skip if no matching GciPart
                    }

                    $qty = $part->inventory->on_hand;
                    if ($qty <= $minQty) {
                        continue;
                    }

                    try {
                        // Decrement source
                        $part->inventory->decrement('on_hand', $qty);

                        // Increment target
                        $targetInv = GciInventory::firstOrCreate(
                            ['gci_part_id' => $gciPart->id],
                            ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()]
                        );
                        $targetInv->increment('on_hand', $qty);
                        $targetInv->update(['as_of_date' => now()]);

                        // Log transfer
                        InventoryTransfer::create([
                            'part_id' => $part->id,
                            'gci_part_id' => $gciPart->id,
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
            });

            $message = "Auto-sync completed. Transferred {$transferred} parts.";
            if (!empty($errors)) {
                $message .= ' Errors: ' . implode(', ', $errors);
            }

            return redirect()
                ->route('inventory.transfers.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
