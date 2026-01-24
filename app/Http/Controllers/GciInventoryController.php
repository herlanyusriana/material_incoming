<?php

namespace App\Http\Controllers;

use App\Models\GciInventory;
use Illuminate\Http\Request;

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
            ->when($classification !== '', fn ($q) => $q->whereHas('part', fn ($qp) => $qp->where('classification', $classification)))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->whereHas('part', fn ($qp) => $qp->where('status', $status)))
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
}

