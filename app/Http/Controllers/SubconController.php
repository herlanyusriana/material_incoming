<?php

namespace App\Http\Controllers;

use App\Models\BomItem;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
use App\Models\SubconOrder;
use App\Models\SubconOrderReceive;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

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
            sum(case when status = 'sent' then 1 else 0 end) as sent,
            sum(case when status = 'partial' then 1 else 0 end) as partial,
            sum(case when status = 'completed' then 1 else 0 end) as completed,
            coalesce(sum(case when status in ('sent','partial') then qty_sent - qty_received - qty_rejected else 0 end), 0) as total_outstanding
        ")->first();

        $vendors = Vendor::query()
            ->where('vendor_type', 'tolling')
            ->orderBy('vendor_name')
            ->get(['id', 'vendor_name']);

        return view('subcon.index', compact('orders', 'stats', 'vendors'));
    }

    public function create()
    {
        $vendorColumns = ['id', 'vendor_name'];
        if (Schema::hasColumn('vendors', 'vendor_code')) {
            $vendorColumns[] = 'vendor_code';
        }
        $vendors = Vendor::query()
            ->where('vendor_type', 'tolling')
            ->orderBy('vendor_name')
            ->get($vendorColumns);

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
            'contract_no' => 'required|string|max:100',
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('vendor_type', 'tolling'))],
            'gci_part_id' => 'required|exists:gci_parts,id',
            'bom_item_id' => 'nullable|exists:bom_items,id',
            'process_type' => 'required|string|max:50',
            'qty_sent' => 'required|numeric|min:0.0001',
            'sent_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:sent_date',
            'send_location_code' => ['required', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // Auto-generate order number with lock to prevent race condition
                $today = now()->format('Ymd');
                $lastOrder = SubconOrder::where('order_no', 'like', "SC-{$today}-%")
                    ->lockForUpdate()
                    ->orderByDesc('order_no')
                    ->first();
                $seq = $lastOrder
                    ? ((int) substr($lastOrder->order_no, -3)) + 1
                    : 1;
                $validated['order_no'] = sprintf('SC-%s-%03d', $today, $seq);
                $validated['contract_no'] = strtoupper(trim((string) $validated['contract_no']));
                $validated['status'] = 'sent';
                $validated['created_by'] = Auth::id();
                $validated['send_location_code'] = strtoupper(trim((string) $validated['send_location_code']));
                $validated['sent_posted_at'] = now();
                $validated['sent_posted_by'] = Auth::id();

                $order = SubconOrder::create($validated);

                LocationInventory::consumeStock(
                    null,
                    $validated['send_location_code'],
                    (float) $validated['qty_sent'],
                    null,
                    (int) $validated['gci_part_id'],
                    'SUBCON_SEND',
                    $order->order_no
                );

                return redirect()->route('subcon.index')
                    ->with('success', "Subcon Order {$validated['order_no']} created.");
            });
        } catch (\Throwable $e) {
            \Log::error('Subcon Order create failed', [
                'error' => $e->getMessage(),
                'input' => $request->except('_token'),
            ]);
            return back()->withInput()->with('error', 'Gagal membuat order: ' . $e->getMessage());
        }
    }

    public function show(SubconOrder $subconOrder)
    {
        $subconOrder->load(['vendor', 'gciPart', 'bomItem', 'receives.creator', 'creator', 'sender']);
        $traceability = LocationInventoryAdjustment::with(['creator'])
            ->where('source_reference', $subconOrder->order_no)
            ->orderBy('adjusted_at')
            ->orderBy('id')
            ->get();

        return view('subcon.show', compact('subconOrder', 'traceability'));
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
            'receive_location_code' => ['nullable', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
            'reject_location_code' => ['nullable', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['qty_good'] = (float) ($validated['qty_good'] ?? 0);
        $validated['qty_rejected'] = (float) ($validated['qty_rejected'] ?? 0);

        if ($validated['qty_good'] <= 0 && $validated['qty_rejected'] <= 0) {
            return back()->withInput()->with('error', 'Isi minimal Qty Good atau Qty Rejected lebih dari nol.');
        }

        if ($validated['qty_good'] > 0 && empty($validated['receive_location_code'])) {
            return back()->withInput()->with('error', 'WH Receive Location wajib diisi jika Qty Good lebih dari nol.');
        }

        if ($validated['qty_rejected'] > 0 && empty($validated['reject_location_code'])) {
            return back()->withInput()->with('error', 'WH Reject Location wajib diisi jika Qty Rejected lebih dari nol.');
        }

        $validated['created_by'] = Auth::id();
        $validated['receive_location_code'] = !empty($validated['receive_location_code'])
            ? strtoupper(trim((string) $validated['receive_location_code']))
            : null;
        $validated['reject_location_code'] = !empty($validated['reject_location_code'])
            ? strtoupper(trim((string) $validated['reject_location_code']))
            : null;
        $validated['posted_to_wh_at'] = $validated['qty_good'] > 0 ? now() : null;
        $validated['reject_posted_to_wh_at'] = $validated['qty_rejected'] > 0 ? now() : null;

        DB::transaction(function () use ($subconOrder, $validated) {
            $receive = $subconOrder->receives()->create($validated);

            if ((float) $validated['qty_good'] > 0) {
                LocationInventory::updateStock(
                    null,
                    $validated['receive_location_code'],
                    (float) $validated['qty_good'],
                    null,
                    $validated['received_date'],
                    (int) $subconOrder->gci_part_id,
                    'SUBCON_RECEIVE',
                    $subconOrder->order_no
                );
            }

            if ((float) $validated['qty_rejected'] > 0) {
                LocationInventory::updateStock(
                    null,
                    $validated['reject_location_code'],
                    (float) $validated['qty_rejected'],
                    null,
                    $validated['received_date'],
                    (int) $subconOrder->gci_part_id,
                    'SUBCON_REJECT_RECEIVE',
                    $subconOrder->order_no
                );
            }

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
