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

        $purchaseOrder->update([
            'status' => 'Released',
            'released_at' => now(),
            'released_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase Order released to vendor.');
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.part', 'approvedBy', 'releasedBy']);
        return view('purchasing.purchase-orders.print', compact('purchaseOrder'));
    }
}
