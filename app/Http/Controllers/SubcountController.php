<?php

namespace App\Http\Controllers;

use App\Models\SubcountBatch;
use App\Models\SubcountPackagingRecord;
use Illuminate\Http\Request;

class SubcountController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = SubcountBatch::query();

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $baseQuery->where(function ($query) use ($search) {
                $query->where('subcount_no', 'like', "%{$search}%")
                    ->orWhere('subcon_order_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('part_info', 'like', "%{$search}%")
                    ->orWhere('operator_name', 'like', "%{$search}%")
                    ->orWhereHas('subconOrder.vendor', fn ($vendorQuery) => $vendorQuery->where('vendor_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('received_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('received_at', '<=', $request->query('date_to'));
        }

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'records' => (clone $baseQuery)->withCount('records')->get()->sum('records_count'),
            'net' => (float) (clone $baseQuery)->sum('total_net_weight_kg'),
        ];

        $subcounts = $baseQuery
            ->with(['subconOrder.vendor', 'latestRecord'])
            ->withCount('records')
            ->latest('received_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('subcounts.index', compact('subcounts', 'summary'));
    }

    public function show(SubcountBatch $subcount)
    {
        $subcount->load(['records', 'subconOrder.vendor', 'subconOrder.rmPart', 'subconOrder.gciPart']);

        return view('subcounts.show', compact('subcount'));
    }

    public function updateRecordNetto(Request $request, SubcountPackagingRecord $record)
    {
        $validated = $request->validate([
            'net_item_weight_kg' => ['required', 'numeric', 'min:0'],
        ]);

        $netto = (float) $validated['net_item_weight_kg'];
        $brutto = (float) $record->gross_weight_kg;

        if ($netto >= $brutto) {
            return back()->withErrors([
                'net_item_weight_kg' => 'Netto harus lebih kecil dari brutto. Timbang ulang jika netto lebih besar atau sama.',
            ]);
        }

        $record->update(['net_item_weight_kg' => $netto]);
        $batch = $record->batch;
        $batch?->update([
            'total_net_weight_kg' => $batch->records()->sum('net_item_weight_kg'),
        ]);

        return back()->with('success', 'Netto berhasil disimpan.');
    }
}
