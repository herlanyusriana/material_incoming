<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\BomItem;
use App\Models\Customer;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\OspOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            sum(case when status = 'received' then 1 else 0 end) as received,
            sum(case when status = 'in_progress' then 1 else 0 end) as in_progress,
            sum(case when status = 'ready' then 1 else 0 end) as ready,
            sum(case when status = 'shipped' then 1 else 0 end) as shipped
        ")->first();

        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('outgoing.osp.index', compact('orders', 'stats', 'customers'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        // OSP parts must use a dedicated outgoing document flow, separate from normal FG outgoing.
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

        try {
            return DB::transaction(function () use ($validated) {
                $today = now()->format('Ymd');
                $lastOrder = OspOrder::where('order_no', 'like', "OSP-{$today}-%")
                    ->lockForUpdate()
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
            });
        } catch (\Throwable $e) {
            \Log::error('OSP Order create failed', [
                'error' => $e->getMessage(),
                'input' => $request->except('_token'),
            ]);
            return back()->withInput()->with('error', 'Gagal membuat order: ' . $e->getMessage());
        }
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

        $qtyShipped = (float) $validated['qty_shipped'];
        $qtyAvailableForOsp = (float) $ospOrder->qty_received_material;
        if ($qtyShipped > $qtyAvailableForOsp) {
            return back()->with('error', 'Qty outgoing OSP melebihi qty dokumen OSP.');
        }

        $defaultLocation = strtoupper(trim((string) ($ospOrder->gciPart?->default_location ?? '')));
        if ($defaultLocation === '') {
            return back()->with('error', 'Default location FG belum diset. OSP outgoing tidak bisa memotong inventory.');
        }

        DB::transaction(function () use ($ospOrder, $qtyShipped, $validated, $defaultLocation) {
            LocationInventory::consumeStock(
                null,
                $defaultLocation,
                $qtyShipped,
                null,
                (int) $ospOrder->gci_part_id,
                'OSP_OUTGOING',
                $ospOrder->order_no
            );

            $ospOrder->update([
                'qty_shipped' => $qtyShipped,
                'shipped_date' => $validated['shipped_date'],
                'status' => 'shipped',
            ]);
        });

        return back()->with('success', 'Order marked as shipped.');
    }
}
