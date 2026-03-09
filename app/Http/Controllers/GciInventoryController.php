<?php

namespace App\Http\Controllers;

use App\Exports\GciInventoryExport;
use App\Models\GciInventory;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GciInventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $status = strtolower(trim((string) $request->query('status', '')));
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $query = GciInventory::query()
            ->with('part.customer')
            ->when($classification !== '', fn($q) => $q->whereHas('part', fn($qp) => $qp->where('classification', $classification)))
            ->when(in_array($status, ['active', 'inactive'], true), fn($q) => $q->whereHas('part', fn($qp) => $qp->where('status', $status)))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->orderByDesc('on_hand')
            ->orderBy('gci_part_id');

        $rows = $query->paginate($perPage)->withQueryString();

        return view('inventory.gci_inventory', compact('rows', 'search', 'classification', 'status', 'perPage'));
    }

    public function export(Request $request)
    {
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $status = strtolower(trim((string) $request->query('status', '')));
        $search = trim((string) $request->query('search', ''));

        $suffix = $classification !== '' ? '_' . strtolower($classification) : '';
        $filename = "gci_inventory{$suffix}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new GciInventoryExport($classification, $status, $search),
            $filename
        );
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'gci_part_id' => 'required|integer|exists:gci_parts,id',
            'default_location' => 'nullable|string|max:50',
        ]);

        $part = GciPart::findOrFail($request->gci_part_id);
        $part->update([
            'default_location' => $request->default_location ? strtoupper(trim($request->default_location)) : null,
        ]);

        return response()->json([
            'success' => true,
            'default_location' => $part->default_location,
        ]);
    }
}

