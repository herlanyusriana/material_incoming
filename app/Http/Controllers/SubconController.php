<?php

namespace App\Http\Controllers;

use App\Models\BomItem;
use App\Models\GciPart;
use App\Models\SubconOrder;
use App\Models\SubconOrderReceive;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubconController extends Controller
{
    public function index(Request $request)
    {
        $query = SubconOrder::with(['vendor', 'gciPart', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('date_from')) {
            $query->where('sent_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('sent_date', '<=', $request->date_to);
        }

        $orders = $query->paginate(25)->withQueryString();

        // Stats
        $stats = SubconOrder::selectRaw("
            count(*) as total,
            count(*) filter (where status = 'sent') as sent,
            count(*) filter (where status = 'partial') as partial,
            count(*) filter (where status = 'completed') as completed,
            coalesce(sum(qty_sent - qty_received - qty_rejected) filter (where status in ('sent','partial')), 0) as total_outstanding
        ")->first();

        $vendors = Vendor::orderBy('vendor_name')->get(['id', 'vendor_name']);

        return view('subcon.index', compact('orders', 'stats', 'vendors'));
    }

    public function create()
    {
        $vendors = Vendor::orderBy('vendor_name')->get(['id', 'vendor_name', 'vendor_code']);

        // Get WIP parts that are used in BOM items with special='T'
        $subconParts = BomItem::where('special', 'T')
            ->whereNotNull('wip_part_id')
            ->with('wipPart')
            ->get()
            ->unique('wip_part_id')
            ->map(fn($item) => [
                'id' => $item->wip_part_id,
                'part_no' => $item->wipPart->part_no ?? $item->wip_part_no,
                'part_name' => $item->wipPart->part_name ?? $item->wip_part_name,
                'process_name' => $item->process_name,
                'bom_item_id' => $item->id,
            ])
            ->values();

        return view('subcon.create', compact('vendors', 'subconParts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'gci_part_id' => 'required|exists:gci_parts,id',
            'bom_item_id' => 'nullable|exists:bom_items,id',
            'process_type' => 'required|string|max:50',
            'qty_sent' => 'required|numeric|min:0.0001',
            'sent_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:sent_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Auto-generate order number
        $today = now()->format('Ymd');
        $lastOrder = SubconOrder::where('order_no', 'like', "SC-{$today}-%")
            ->orderByDesc('order_no')
            ->first();
        $seq = $lastOrder
            ? ((int) substr($lastOrder->order_no, -3)) + 1
            : 1;
        $validated['order_no'] = sprintf('SC-%s-%03d', $today, $seq);
        $validated['status'] = 'sent';
        $validated['created_by'] = Auth::id();

        SubconOrder::create($validated);

        return redirect()->route('subcon.index')
            ->with('success', "Subcon Order {$validated['order_no']} created.");
    }

    public function show(SubconOrder $subconOrder)
    {
        $subconOrder->load(['vendor', 'gciPart', 'bomItem', 'receives.creator', 'creator']);

        return view('subcon.show', compact('subconOrder'));
    }

    public function receive(Request $request, SubconOrder $subconOrder)
    {
        if (in_array($subconOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot receive on a completed/cancelled order.');
        }

        $validated = $request->validate([
            'qty_good' => 'required|numeric|min:0',
            'qty_rejected' => 'nullable|numeric|min:0',
            'received_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['qty_rejected'] = $validated['qty_rejected'] ?? 0;
        $validated['created_by'] = Auth::id();

        DB::transaction(function () use ($subconOrder, $validated) {
            $subconOrder->receives()->create($validated);

            $subconOrder->increment('qty_received', $validated['qty_good']);
            $subconOrder->increment('qty_rejected', $validated['qty_rejected']);

            $subconOrder->refresh();

            // Update status
            $outstanding = $subconOrder->qty_outstanding;
            if ($outstanding <= 0) {
                $subconOrder->update([
                    'status' => 'completed',
                    'received_date' => $validated['received_date'],
                ]);
            } else {
                $subconOrder->update(['status' => 'partial']);
            }
        });

        return back()->with('success', 'Receive recorded successfully.');
    }

    public function cancel(SubconOrder $subconOrder)
    {
        if ($subconOrder->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed order.');
        }

        $subconOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Order cancelled.');
    }

    public function parts()
    {
        $parts = BomItem::where('special', 'T')
            ->whereNotNull('wip_part_id')
            ->with('wipPart')
            ->get()
            ->unique('wip_part_id')
            ->map(fn($item) => [
                'id' => $item->wip_part_id,
                'part_no' => $item->wipPart->part_no ?? $item->wip_part_no,
                'part_name' => $item->wipPart->part_name ?? $item->wip_part_name,
                'process_name' => $item->process_name,
                'bom_item_id' => $item->id,
            ])
            ->values();

        return response()->json($parts);
    }
}
