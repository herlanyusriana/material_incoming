<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Vendor;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with(['vendor', 'items.part'])
            ->latest()
            ->paginate(15);

        return view('purchasing.purchase-orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $pr = null;
        if ($request->has('pr_id')) {
            $pr = PurchaseRequest::with('items.part')->findOrFail($request->pr_id);
        }

        $vendors = Vendor::all();
        return view('purchasing.purchase-orders.create', compact('pr', 'vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'notes' => 'nullable|string',
            'pr_id' => 'nullable|exists:purchase_requests,id',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'vendor_id' => $validated['vendor_id'],
                'status' => 'Pending',
                'notes' => $validated['notes'],
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = $item['unit_price'] * $item['qty'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_request_item_id' => $item['pr_item_id'] ?? null,
                    'part_id' => $item['part_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $purchaseOrder->update(['total_amount' => $totalAmount]);

            if ($validated['pr_id']) {
                PurchaseRequest::where('id', $validated['pr_id'])->update(['status' => 'Converted']);
            }

            DB::commit();
            return redirect()->route('purchasing.purchase-orders.index')
                ->with('success', 'Purchase Order created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating PO: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.part', 'approvedBy', 'releasedBy']);
        return view('purchasing.purchase-orders.show', compact('purchaseOrder'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Pending') {
            return back()->with('error', 'Only pending orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase Order approved.');
    }

    public function release(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'Approved') {
            return back()->with('error', 'Only approved orders can be released.');
        }

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'status' => 'Released',
                'released_at' => now(),
                'released_by' => auth()->id(),
            ]);

            // Create Draft Departure in Incoming
            $purchaseOrder->load('items.part');

            $arrival = \App\Models\Arrival::create([
                'arrival_no' => \App\Models\Arrival::generateArrivalNo(),
                'invoice_no' => 'DRAFT-PO-' . $purchaseOrder->po_number,
                'invoice_date' => now(),
                'vendor_id' => $purchaseOrder->vendor_id,
                'status' => 'pending',
                'currency' => 'USD', // Assumed default, can be edited later
                'created_by' => auth()->id(),
                'purchase_order_id' => $purchaseOrder->id,
                'notes' => 'Auto-generated from PO ' . $purchaseOrder->po_number,
            ]);

            foreach ($purchaseOrder->items as $item) {
                \App\Models\ArrivalItem::create([
                    'arrival_id' => $arrival->id,
                    'part_id' => $item->part_id,
                    'qty_goods' => (int) $item->qty, // Assuming integer logic for incoming
                    'unit_goods' => null, // To be filled by incoming team
                    'weight_nett' => 0,
                    'weight_gross' => 0,
                    'price' => '0.000', // Need to compute later or leave 0 for draft
                    'total_price' => $item->subtotal ?? 0,
                    'purchase_order_item_id' => $item->id,
                    'notes' => 'Auto-generated item from PO',
                ]);
            }

            DB::commit();
            return back()->with('success', 'Purchase Order released to vendor. Draft Departure automatically created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to release PO: ' . $e->getMessage());
        }
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.part', 'approvedBy', 'releasedBy']);
        return view('purchasing.purchase-orders.print', compact('purchaseOrder'));
    }
}
