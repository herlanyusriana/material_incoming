<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\BomItem;
use App\Models\Customer;
use App\Models\GciPart;
use App\Models\OspOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OspController extends Controller
{
    public function index(Request $request)
    {
        $query = OspOrder::with(['customer', 'gciPart', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $orders = $query->paginate(25)->withQueryString();

        $stats = OspOrder::selectRaw("
            count(*) as total,
            count(*) filter (where status = 'received') as received,
            count(*) filter (where status = 'in_progress') as in_progress,
            count(*) filter (where status = 'ready') as ready,
            count(*) filter (where status = 'shipped') as shipped
        ")->first();

        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('outgoing.osp.index', compact('orders', 'stats', 'customers'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        // Get parts from BOM items where special='OSP' and make_or_buy='free_issue'
        $ospParts = BomItem::where('special', 'OSP')
            ->where('make_or_buy', 'free_issue')
            ->whereNotNull('wip_part_id')
            ->with(['wipPart', 'bom.part'])
            ->get()
            ->map(fn($item) => [
                'gci_part_id' => $item->bom->part_id ?? null,
                'part_no' => $item->bom->part->part_no ?? '',
                'part_name' => $item->bom->part->part_name ?? '',
                'bom_item_id' => $item->id,
            ])
            ->filter(fn($item) => $item['gci_part_id'] !== null)
            ->unique('gci_part_id')
            ->values();

        return view('outgoing.osp.create', compact('customers', 'ospParts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'gci_part_id' => 'required|exists:gci_parts,id',
            'bom_item_id' => 'nullable|exists:bom_items,id',
            'qty_received_material' => 'required|numeric|min:0.0001',
            'received_date' => 'required|date',
            'target_ship_date' => 'nullable|date|after_or_equal:received_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $today = now()->format('Ymd');
        $lastOrder = OspOrder::where('order_no', 'like', "OSP-{$today}-%")
            ->orderByDesc('order_no')
            ->first();
        $seq = $lastOrder
            ? ((int) substr($lastOrder->order_no, -3)) + 1
            : 1;
        $validated['order_no'] = sprintf('OSP-%s-%03d', $today, $seq);
        $validated['status'] = 'received';
        $validated['created_by'] = Auth::id();

        OspOrder::create($validated);

        return redirect()->route('outgoing.osp.index')
            ->with('success', "OSP Order {$validated['order_no']} created.");
    }

    public function show(OspOrder $ospOrder)
    {
        $ospOrder->load(['customer', 'gciPart', 'bomItem', 'creator']);

        return view('outgoing.osp.show', compact('ospOrder'));
    }

    public function updateProgress(Request $request, OspOrder $ospOrder)
    {
        if (in_array($ospOrder->status, ['shipped', 'cancelled'])) {
            return back()->with('error', 'Cannot update a shipped/cancelled order.');
        }

        $validated = $request->validate([
            'qty_assembled' => 'required|numeric|min:0',
        ]);

        $ospOrder->update([
            'qty_assembled' => $validated['qty_assembled'],
            'status' => $validated['qty_assembled'] >= $ospOrder->qty_received_material ? 'ready' : 'in_progress',
        ]);

        return back()->with('success', 'Progress updated.');
    }

    public function ship(Request $request, OspOrder $ospOrder)
    {
        if ($ospOrder->status === 'shipped') {
            return back()->with('error', 'Already shipped.');
        }

        $validated = $request->validate([
            'qty_shipped' => 'required|numeric|min:0.0001',
            'shipped_date' => 'required|date',
        ]);

        $ospOrder->update([
            'qty_shipped' => $validated['qty_shipped'],
            'shipped_date' => $validated['shipped_date'],
            'status' => 'shipped',
        ]);

        return back()->with('success', 'Order marked as shipped.');
    }
}
