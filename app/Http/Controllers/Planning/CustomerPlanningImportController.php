<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Imports\CustomerPlanningRowsImport;
use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPlanningImport;
use App\Models\CustomerPlanningRow;
use App\Exports\CustomerPlanningTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CustomerPlanningImportController extends Controller
{
    private function validateMinggu(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (bool) preg_match('/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/', $value);
    }

    public function index(Request $request)
    {
        $importId = $request->query('import_id');

        $imports = CustomerPlanningImport::query()
            ->with('customer')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $rows = null;
        $translatedByRowId = [];
        $unmappedCustomerParts = collect();
        $importCustomerId = null;
        if ($importId) {
            $importCustomerId = CustomerPlanningImport::query()->whereKey($importId)->value('customer_id');
            $rows = CustomerPlanningRow::query()
                ->with('part')
                ->where('import_id', $importId)
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString();

            $translated = DB::table('customer_planning_rows as r')
                ->join('customer_planning_imports as i', 'i.id', '=', 'r.import_id')
                ->join('customer_parts as cp', function ($join) {
                    $join->on('cp.customer_id', '=', 'i.customer_id')
                        ->on('cp.customer_part_no', '=', 'r.customer_part_no');
                })
                ->join('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
                ->join('gci_parts as gp', 'gp.id', '=', 'cpc.part_id')
                ->where('r.import_id', $importId)
                ->select([
                    'r.id as row_id',
                    'gp.part_no',
                    'gp.part_name',
                    'cpc.usage_qty',
                    'r.qty as customer_qty',
                    DB::raw('(r.qty * cpc.usage_qty) as demand_qty'),
                ])
                ->orderBy('r.id')
                ->orderBy('gp.part_no')
                ->get();

            foreach ($translated as $t) {
                $translatedByRowId[(int) $t->row_id][] = [
                    'part_no' => $t->part_no,
                    'part_name' => $t->part_name,
                    'usage_qty' => (float) $t->usage_qty,
                    'demand_qty' => (float) $t->demand_qty,
                ];
            }

            $unmappedCustomerParts = DB::table('customer_planning_rows')
                ->where('import_id', $importId)
                ->where('row_status', 'unknown_mapping')
                ->select([
                    'customer_part_no',
                    DB::raw('COUNT(*) as rows_count'),
                    DB::raw('SUM(qty) as total_qty'),
                ])
                ->groupBy('customer_part_no')
                ->orderBy('customer_part_no')
                ->get();
        }

        $customers = Customer::query()->orderBy('code')->get();

        return view('planning.customer_planning_imports.index', compact('imports', 'rows', 'customers', 'importId', 'translatedByRowId', 'unmappedCustomerParts', 'importCustomerId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $file = $request->file('file');
        $fileName = $file?->getClientOriginalName();

        $importer = new CustomerPlanningRowsImport();
        Excel::import($importer, $file);
        $rows = $importer->rows ?? collect();

        $totalRows = 0;
        $accepted = 0;
        $rejected = 0;

        DB::transaction(function () use ($validated, $fileName, $rows, &$totalRows, &$accepted, &$rejected, $request) {
            $import = CustomerPlanningImport::create([
                'customer_id' => (int) $validated['customer_id'],
                'file_name' => $fileName,
                'uploaded_by' => $request->user()?->id,
                'status' => 'completed',
            ]);

            foreach ($rows as $row) {
                $totalRows++;
                $customerPartNo = strtoupper(trim((string) ($row['customer_part_no'] ?? $row['customer_part'] ?? '')));
                $minggu = strtoupper(trim((string) ($row['minggu'] ?? $row['week'] ?? '')));
                $qtyRaw = $row['qty'] ?? $row['quantity'] ?? null;
                $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : null;

                $status = 'accepted';
                $error = null;
                $partId = null;

                if ($customerPartNo === '' || !$this->validateMinggu($minggu) || $qty === null) {
                    $status = 'rejected';
                    $error = 'Invalid row data.';
                } else {
                    $mapping = CustomerPart::query()
                        ->where('customer_id', $validated['customer_id'])
                        ->where('customer_part_no', $customerPartNo)
                        ->withCount('components')
                        ->first();

                    if (!$mapping) {
                        $status = 'unknown_mapping';
                        $error = 'Customer part not mapped.';
                    } elseif (($mapping->status ?? 'active') !== 'active') {
                        $status = 'unknown_mapping';
                        $error = 'Customer part mapping is inactive.';
                    } elseif ($mapping->components_count < 1) {
                        $status = 'unknown_mapping';
                        $error = 'Customer part has no mapped components.';
                    } elseif ($mapping->components_count === 1) {
                        $partId = $mapping->components()->value('part_id');
                    }
                }

                CustomerPlanningRow::create([
                    'import_id' => $import->id,
                    'customer_part_no' => $customerPartNo,
                    'minggu' => $minggu,
                    'qty' => $qty ?? 0,
                    'part_id' => $partId,
                    'row_status' => $status,
                    'error_message' => $error,
                ]);

                if ($status === 'accepted') {
                    $accepted++;
                } else {
                    $rejected++;
                }
            }

            $import->update([
                'total_rows' => $totalRows,
                'accepted_rows' => $accepted,
                'rejected_rows' => $rejected,
            ]);
        });

        return back()->with('success', 'Customer planning imported.');
    }

    public function template()
    {
        $filename = 'customer_planning_template_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new CustomerPlanningTemplateExport(), $filename);
    }
}
