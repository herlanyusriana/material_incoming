<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPo;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\FgInventory;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    public function index()
    {
        $deliveryNotes = DeliveryNote::with(['customer', 'items.part'])
            ->latest()
            ->paginate(25);

        return view('outgoing.delivery_notes.index', compact('deliveryNotes'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $gciParts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();
        
        return view('outgoing.delivery_notes.create', compact('customers', 'gciParts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dn_no' => ['required', 'string', 'unique:delivery_notes,dn_no'],
            'customer_id' => ['required', 'exists:customers,id'],
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.gci_part_id' => ['required', 'exists:gci_parts,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.customer_po_id' => ['nullable', 'exists:customer_pos,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $dn = DeliveryNote::create([
                'dn_no' => $validated['dn_no'],
                'customer_id' => $validated['customer_id'],
                'delivery_date' => $validated['delivery_date'],
                'notes' => $validated['notes'],
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                DnItem::create([
                    'dn_id' => $dn->id,
                    'gci_part_id' => $item['gci_part_id'],
                    'qty' => $item['qty'],
                    'customer_po_id' => $item['customer_po_id'] ?? null,
                ]);
            }
        });

        return redirect()->route('outgoing.delivery-notes.index')->with('success', 'Delivery Note created.');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'items.part', 'items.customerPo']);
        return view('outgoing.delivery_notes.show', compact('deliveryNote'));
    }

    public function ship(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status === 'shipped') {
            return back()->with('error', 'Delivery Note already shipped.');
        }

        DB::transaction(function () use ($deliveryNote) {
            $deliveryNote->update(['status' => 'shipped']);

            foreach ($deliveryNote->items as $item) {
                $inventory = FgInventory::firstOrCreate(
                    ['gci_part_id' => $item->gci_part_id],
                    ['qty_on_hand' => 0]
                );

                $inventory->decrement('qty_on_hand', $item->qty);
            }
        });

        return back()->with('success', 'Delivery Note shipped and inventory deducted.');
    }

    public function destroy(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status === 'shipped') {
            return back()->with('error', 'Cannot delete shipped Delivery Note.');
        }

        $deliveryNote->delete();
        return redirect()->route('outgoing.delivery-notes.index')->with('success', 'Delivery Note deleted.');
    }
}
