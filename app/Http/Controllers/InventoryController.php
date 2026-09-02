<?php

namespace App\Http\Controllers;

use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Exports\InventoryExport;
use App\Imports\LocationInventoryImport;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function receives(Request $request)
    {
        $partId = $request->query('part_id');
        $qcStatus = $request->query('qc_status');
        $search = trim((string) $request->query('search', ''));

        $parts = GciPart::query()->orderBy('part_no')->get();

        $receives = Receive::query()
            ->with(['arrivalItem.gciPart', 'arrivalItem.vendorPart', 'arrivalItem.arrival'])
            ->when($partId, fn($q) => $q->whereHas('arrivalItem', fn($qq) => $qq->where('gci_part_id', $partId)))
            ->when($qcStatus, fn($q) => $q->where('qc_status', $qcStatus))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('tag', 'like', '%' . $search . '%')
                        ->orWhereHas('arrivalItem', function ($qqq) use ($search) {
                            $qqq->whereHas('arrival', fn ($a) => $a->where('invoice_no', 'like', '%' . $search . '%'))
                                ->orWhereHas('gciPart', function ($qqqq) use ($search) {
                                    $qqqq->where('part_no', 'like', '%' . $search . '%')
                                        ->orWhere('part_name', 'like', '%' . $search . '%');
                                })
                                ->orWhereHas('vendorPart', function ($qqqq) use ($search) {
                                    $qqqq->where('vendor_part_no', 'like', '%' . $search . '%')
                                        ->orWhere('vendor_part_name', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $locationCodes = $receives->getCollection()
            ->pluck('location_code')
            ->filter(fn($code) => is_string($code) && trim($code) !== '')
            ->map(fn($code) => strtoupper(trim($code)))
            ->unique()
            ->values();

        $locationMap = $locationCodes->isEmpty() || !Schema::hasTable('warehouse_locations')
            ? collect()
            : WarehouseLocation::query()
                ->whereIn('location_code', $locationCodes->all())
                ->get()
                ->keyBy('location_code');

        return view('inventory.receives', compact('receives', 'parts', 'partId', 'qcStatus', 'locationMap'));
    }

    public function export()
    {
        $filename = 'inventory_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new InventoryExport(), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new LocationInventoryImport();
        Excel::import($import, $validated['file']);

        $msg = "Import selesai: {$import->imported} rows updated, {$import->skipped} skipped.";
        if ($import->created > 0) {
            $msg .= " {$import->created} new parts created.";
        }

        return back()->with('success', $msg);
    }

    public function searchReceives(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $receives = Receive::query()
            ->with(['arrivalItem.vendorPart', 'arrivalItem.gciPart', 'arrivalItem.arrival'])
            ->where(function ($q) use ($query) {
                $q->where('tag', 'like', '%' . $query . '%')
                    ->orWhereHas('arrivalItem', function ($qq) use ($query) {
                        $qq->whereHas('arrival', fn ($a) => $a->where('invoice_no', 'like', '%' . $query . '%'))
                            ->orWhereHas('gciPart', function ($qqq) use ($query) {
                                $qqq->where('part_no', 'like', '%' . $query . '%')
                                    ->orWhere('part_name', 'like', '%' . $query . '%');
                            })
                            ->orWhereHas('vendorPart', function ($qqq) use ($query) {
                                $qqq->where('vendor_part_no', 'like', '%' . $query . '%')
                                    ->orWhere('vendor_part_name', 'like', '%' . $query . '%');
                            });
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($receive) {
                $part = $receive->arrivalItem?->gciPart;
                $vendorPart = $receive->arrivalItem?->vendorPart;
                $arrival = $receive->arrivalItem?->arrival;
                return [
                    'id' => $receive->id,
                    'tag' => $receive->tag,
                    'part_no' => $part?->part_no ?? $vendorPart?->vendor_part_no ?? '-',
                    'part_name' => $part?->part_name ?? $vendorPart?->vendor_part_name ?? '-',
                    'invoice_no' => $arrival?->invoice_no ?? '-',
                    'location_code' => $receive->location_code ?? '-',
                ];
            });

        return response()->json($receives);
    }
}
