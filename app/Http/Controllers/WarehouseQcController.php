<?php

namespace App\Http\Controllers;

use App\Models\Receive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseQcController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower(trim((string) $request->query('status', '')));
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $allowed = ['hold', 'reject', 'fail'];
        if ($status !== '' && !in_array($status, $allowed, true)) {
            $status = '';
        }

        $query = Receive::query()
            ->with(['arrivalItem.part', 'arrivalItem.arrival.vendor', 'qcUpdater'])
            ->whereIn(DB::raw('LOWER(qc_status)'), $allowed)
            ->when($status !== '', fn ($q) => $q->whereRaw('LOWER(qc_status) = ?', [$status]))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qq) use ($s) {
                    $qq->where('tag', 'like', '%' . $s . '%')
                        ->orWhereHas('arrivalItem.arrival', fn ($qa) => $qa->where('arrival_no', 'like', '%' . $s . '%'))
                        ->orWhereHas('arrivalItem.part', fn ($qp) => $qp->where('part_no', 'like', '%' . $s . '%'));
                });
            })
            ->latest();

        $rows = $query->paginate($perPage)->withQueryString();

        return view('warehouse.qc.index', compact('rows', 'search', 'status', 'perPage'));
    }

    public function update(Request $request, Receive $receive)
    {
        $validated = $request->validate([
            'qc_status' => ['required', 'string', Rule::in(['pass', 'hold', 'reject'])],
            'qc_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = strtolower(trim((string) $validated['qc_status']));
        $note = isset($validated['qc_note']) ? trim((string) $validated['qc_note']) : '';
        $note = $note !== '' ? $note : null;

        $receive->update([
            'qc_status' => $newStatus,
            'qc_note' => $note,
            'qc_updated_at' => now(),
            'qc_updated_by' => (int) ($request->user()?->id ?? 0) ?: null,
        ]);

        $msg = $newStatus === 'pass'
            ? 'QC updated to PASS. Silakan lanjut Putaway.'
            : 'QC updated.';

        return back()->with('success', $msg);
    }
}

