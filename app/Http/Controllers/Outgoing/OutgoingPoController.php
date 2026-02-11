<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\OutgoingPo;
use App\Models\OutgoingPoItem;
use App\Models\Customer;
use App\Models\GciPart;
use App\Models\CustomerPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutgoingPoController extends Controller
{
    public function index(Request $request)
    {
        $query = OutgoingPo::with(['customer', 'items'])
            ->latest('po_release_date');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('po_no', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pos = $query->paginate(20)->appends($request->query());

        return view('outgoing.po.index', compact('pos'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('outgoing.po.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_no' => 'required|string|max:100|unique:outgoing_pos,po_no',
            'customer_id' => 'required|exists:customers,id',
            'po_release_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.vendor_part_name' => 'required|string|max:255',
            'items.*.gci_part_id' => 'nullable|exists:gci_parts,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.delivery_date' => 'required|date',
        ]);

        $po = DB::transaction(function () use ($validated) {
            $po = OutgoingPo::create([
                'po_no' => $validated['po_no'],
                'customer_id' => $validated['customer_id'],
                'po_release_date' => $validated['po_release_date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $po->items()->create([
                    'vendor_part_name' => $item['vendor_part_name'],
                    'gci_part_id' => $item['gci_part_id'] ?: null,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'delivery_date' => $item['delivery_date'],
                ]);
            }

            return $po;
        });

        return redirect()->route('outgoing.customer-po.show', $po)
            ->with('success', 'PO Outgoing berhasil dibuat.');
    }

    public function show(OutgoingPo $outgoingPo)
    {
        $outgoingPo->load(['customer', 'items.part', 'creator']);
        return view('outgoing.po.show', compact('outgoingPo'));
    }

    public function confirm(OutgoingPo $outgoingPo)
    {
        $outgoingPo->update(['status' => 'confirmed']);
        return back()->with('success', 'PO confirmed.');
    }

    public function complete(OutgoingPo $outgoingPo)
    {
        $outgoingPo->update(['status' => 'completed']);
        return back()->with('success', 'PO marked as completed.');
    }

    public function cancel(OutgoingPo $outgoingPo)
    {
        $outgoingPo->update(['status' => 'cancelled']);
        return back()->with('success', 'PO cancelled.');
    }

    /**
     * AJAX: Search GCI Parts by vendor part name or part no
     */
    public function searchParts(Request $request)
    {
        $term = $request->input('q', '');
        $customerId = $request->input('customer_id');

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        // First try customer parts mapping
        $results = collect();

        if ($customerId) {
            $customerParts = CustomerPart::with(['components.part'])
                ->where('customer_id', $customerId)
                ->where(function ($q) use ($term) {
                    $q->where('customer_part_name', 'like', "%{$term}%")
                        ->orWhere('customer_part_no', 'like', "%{$term}%");
                })
                ->limit(10)
                ->get();

            foreach ($customerParts as $cp) {
                foreach ($cp->components as $comp) {
                    if ($comp->part && $comp->part->classification === 'FG') {
                        $results->push([
                            'gci_part_id' => $comp->part->id,
                            'vendor_part_name' => $cp->customer_part_name ?? $cp->customer_part_no,
                            'part_name' => $comp->part->part_name,
                            'part_no' => $comp->part->part_no,
                            'model' => $comp->part->model ?? '-',
                        ]);
                    }
                }
            }
        }

        // Also search GCI Parts directly
        $gciParts = GciPart::where('status', 'active')
            ->where(function ($q) use ($term) {
                $q->where('part_name', 'like', "%{$term}%")
                    ->orWhere('part_no', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get();

        foreach ($gciParts as $part) {
            // Avoid duplicates
            if (!$results->contains('gci_part_id', $part->id)) {
                $results->push([
                    'gci_part_id' => $part->id,
                    'vendor_part_name' => $part->part_name,
                    'part_name' => $part->part_name,
                    'part_no' => $part->part_no,
                    'model' => $part->model ?? '-',
                ]);
            }
        }

        return response()->json($results->values()->take(15));
    }
}
