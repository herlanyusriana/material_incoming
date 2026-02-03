<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\GciPart;
use App\Models\MrpPurchasePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['requester', 'items.part'])
            ->latest()
            ->paginate(15);

        return view('purchasing.purchase-requests.index', compact('requests'));
    }

    public function create()
    {
        return view('purchasing.purchase-requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.required_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $prNumber = 'PR-' . strtoupper(Str::random(8));
            
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $prNumber,
                'requester_id' => auth()->id(),
                'status' => 'Pending',
                'notes' => $validated['notes'],
            ]);

            $totalAmount = 0;
            $hasItems = false;
            foreach ($validated['items'] as $item) {
                if (!isset($item['selected']) || $item['selected'] != '1') continue;
                
                $hasItems = true;
                // Get price from Part model if available, or just use 0 for now
                $unitPrice = 0; 
                $subtotal = $unitPrice * $item['qty'];
                
                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'part_id' => $item['part_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'required_date' => $item['required_date'],
                ]);
                
                $totalAmount += $subtotal;
            }

            if (!$hasItems) {
                throw new \Exception('No items selected for PR.');
            }

            $purchaseRequest->update(['total_amount' => $totalAmount]);

            DB::commit();
            return redirect()->route('purchasing.purchase-requests.index')
                ->with('success', 'Purchase Request created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating PR: ' . $e->getMessage());
        }
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['requester', 'items.part']);
        return view('purchasing.purchase-requests.show', compact('purchaseRequest'));
    }

    public function approve(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'Pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $purchaseRequest->update(['status' => 'Approved']);
        return back()->with('success', 'Purchase Request approved.');
    }

    public function convertToPo(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'Approved') {
            return back()->with('error', 'Only approved requests can be converted to PO.');
        }

        // Logic for conversion will be implemented in Step 4
        // For now, redirect with message
        return redirect()->route('purchasing.purchase-orders.create', ['pr_id' => $purchaseRequest->id]);
    }

    public function createFromMrp(Request $request)
    {
        // Fetch items from MrpPurchasePlan that haven't been converted to PR yet
        // This logic requires a way to track which plans are already handled
        $plans = MrpPurchasePlan::with('part')
            ->latest()
            ->limit(50)
            ->get();

        return view('purchasing.purchase-requests.create_from_mrp', compact('plans'));
    }
}
