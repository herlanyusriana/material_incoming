<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Vendor;
use App\Models\Part;
use App\Models\GciPart;
use App\Models\GciPartVendor;
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
        $suggestedVendorId = null;
        $itemPrices = [];
        $vendorPartMap = []; // gci_part_id => vendor_id => {id, part_no, part_name}

        if ($request->has('pr_id')) {
            $pr = PurchaseRequest::with('items.part')->findOrFail($request->pr_id);

            $partIds = $pr->items->pluck('part_id')->toArray();
            $vendorLinks = GciPartVendor::whereIn('gci_part_id', $partIds)
                ->whereNotNull('vendor_id')
                ->get();

            $suggestedVendorId = $vendorLinks->groupBy('vendor_id')
                ->sortByDesc(fn($group) => $group->count())
                ->keys()
                ->first();

            foreach ($vendorLinks as $vl) {
                $itemPrices[$vl->gci_part_id][$vl->vendor_id] = (float) $vl->price;
                $vendorPartMap[$vl->gci_part_id][$vl->vendor_id] = [
                    'id' => $vl->id,
                    'part_no' => $vl->vendor_part_no,
                    'part_name' => $vl->vendor_part_name,
                ];
            }
        }

        $vendors = Vendor::all();
        return view('purchasing.purchase-orders.create', compact('pr', 'vendors', 'suggestedVendorId', 'itemPrices', 'vendorPartMap'));
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
            $vendorId = $validated['vendor_id'];
            foreach ($validated['items'] as $item) {
                $subtotal = $item['unit_price'] * $item['qty'];

                // Resolve vendor part from gci_part_vendor pivot
                $vendorLink = GciPartVendor::where('gci_part_id', $item['part_id'])
                    ->where('vendor_id', $vendorId)
                    ->first();

                // Skip item if it doesn't have a vendor link to the selected PO vendor
                if (!$vendorLink) {
                    continue;
                }

                // Also resolve legacy parts table for vendor_part_id (backward compat)
                $vendorPart = Part::where('gci_part_id', $item['part_id'])
                    ->where('vendor_id', $vendorId)
                    ->first();

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_request_item_id' => $item['pr_item_id'] ?? null,
                    'part_id' => $item['part_id'],
                    'vendor_part_id' => $vendorPart?->id,
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
        $purchaseOrder->load(['vendor', 'items.part', 'items.vendorPart', 'approvedBy', 'releasedBy']);
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
            $purchaseOrder->load('items.vendorPart');

            $arrival = \App\Models\Arrival::create([
                'arrival_no' => \App\Models\Arrival::generateArrivalNo(),
                'invoice_no' => 'DRAFT-PO-' . $purchaseOrder->po_number,
                'invoice_date' => now(),
                'vendor_id' => $purchaseOrder->vendor_id,
                'status' => 'pending',
                'currency' => 'USD',
                'created_by' => auth()->id(),
                'purchase_order_id' => $purchaseOrder->id,
                'notes' => 'Auto-generated from PO ' . $purchaseOrder->po_number,
            ]);

            foreach ($purchaseOrder->items as $item) {
                // Use vendor_part_id (parts table) for arrival items
                // Fallback: resolve from gci_part_id + vendor via gci_part_vendor then parts
                $incomingPartId = $item->vendor_part_id;
                if (!$incomingPartId) {
                    // Try to find in parts table via gci_part_vendor -> parts lookup
                    $vendorPart = Part::where('gci_part_id', $item->part_id)
                        ->where('vendor_id', $purchaseOrder->vendor_id)
                        ->first();
                    $incomingPartId = $vendorPart?->id;
                }

                // Also resolve gci_part_id for the arrival item
                $gciPartId = $item->part_id; // PO items already use gci_parts.id

                if (!$incomingPartId) {
                    continue; // Skip items without vendor part mapping
                }

                \App\Models\ArrivalItem::create([
                    'arrival_id' => $arrival->id,
                    'part_id' => $incomingPartId,
                    'gci_part_id' => $gciPartId,
                    'qty_goods' => (int) $item->qty,
                    'unit_goods' => null,
                    'weight_nett' => 0,
                    'weight_gross' => 0,
                    'price' => $item->unit_price ?? '0.000',
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
        $purchaseOrder->load(['vendor', 'items.part', 'items.vendorPart', 'approvedBy', 'releasedBy']);
        return view('purchasing.purchase-orders.print', compact('purchaseOrder'));
    }
}
