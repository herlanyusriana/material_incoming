<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Imports\CustomerPlanningUploadImport;
use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPlanningImport;
use App\Models\CustomerPlanningRow;
use App\Exports\CustomerPlanningTemplateExport;
use App\Exports\CustomerPlanningImportRowsExport;
use App\Exports\CustomerPlanningMonthlyTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Exceptions\SheetNotFoundException;

class CustomerPlanningImportController extends Controller
{
    private function validatePeriod(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (bool) preg_match('/^\d{4}-([W]\d{2}|\d{2})$/', $value);
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
                ->where('row_status', '!=', 'accepted')
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
                ->join('gci_parts as gp', 'gp.id', '=', 'cpc.gci_part_id')
                ->where('r.import_id', $importId)
                ->select([
                    'r.id as row_id',
                    'gp.part_no',
                    'gp.part_name',
                    'cpc.qty_per_unit as usage_qty',
                    'r.qty as customer_qty',
                    DB::raw('(r.qty * cpc.qty_per_unit) as demand_qty'),
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

        try {
            $importer = new CustomerPlanningUploadImport(includeWeekMap: true);
            Excel::import($importer, $file);
        } catch (SheetNotFoundException $e) {
            $importer = new CustomerPlanningUploadImport(includeWeekMap: false);
            Excel::import($importer, $file);
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        if (!$importer->format) {
            $found = collect($importer->detectedHeaders ?? [])
                ->filter(fn ($h) => trim((string) $h) !== '')
                ->take(12)
                ->implode(', ');
            $suffix = $found !== '' ? " Headers ditemukan: {$found}" : '';

            return back()->with('error', 'Format file tidak dikenali. Gunakan template weekly (customer_part_no, minggu, qty) atau template monthly (Part Number + kolom bulan).' . $suffix);
        }

        $normalizedRows = collect();
        if ($importer->format === 'weekly') {
            $normalizedRows = collect($importer->weeklyRows);
        } else {
            // Monthly: require week mapping sheet (option C).
            $originalMonthly = collect($importer->monthlyRows);
            if ($originalMonthly->isEmpty()) {
                return back()->with('error', 'Tidak ada data monthly yang terbaca.');
            }

            // Allow duplicates in the same upload, sum per customer_part_no per month column.
            $originalMonthly = $originalMonthly
                ->groupBy('customer_part_no')
                ->map(function ($rows, $customerPartNo) {
                    $months = [];
                    foreach ($rows as $row) {
                        foreach (($row['months'] ?? []) as $monthHeader => $qty) {
                            $months[$monthHeader] = ($months[$monthHeader] ?? 0) + (float) $qty;
                        }
                    }
                    return [
                        'customer_part_no' => (string) $customerPartNo,
                        'months' => $months,
                    ];
                })
                ->values();

            $weekMapRows = collect($importer->weekMapRows);
            if ($weekMapRows->isEmpty()) {
                return back()->with('error', 'Monthly format but mapping sheet missing. Tambahkan sheet kedua dengan kolom: month_header, minggu, ratio.');
            }

            try {
                $weekMap = $weekMapRows
                    ->groupBy('month_header')
                    ->map(function ($rows, $monthKey) {
                        $items = collect($rows)
                            ->map(function ($r) {
                                return [
                                    'minggu' => strtoupper(trim((string) ($r['minggu'] ?? ''))),
                                    'ratio' => isset($r['ratio']) && is_numeric($r['ratio']) && (float) $r['ratio'] > 0 ? (float) $r['ratio'] : null,
                                ];
                            })
                            ->filter(fn($r) => $r['minggu'] !== '')
                            ->groupBy('minggu')
                            ->map(function ($group, $minggu) {
                                $ratios = collect($group)->pluck('ratio')->filter(fn($v) => $v !== null);
                                $ratioSum = $ratios->sum();
                                $ratio = $ratioSum > 0 ? (float) $ratioSum : null;
                                return ['minggu' => $minggu, 'ratio' => $ratio];
                            })
                            ->values();

                        // Validate minggu format early.
                        $invalidPeriods = $items
                            ->pluck('minggu')
                            ->filter(fn($w) => !$this->validatePeriod($w))
                            ->values();
                        if ($invalidPeriods->isNotEmpty()) {
                            throw new \RuntimeException("Invalid period in WeekMap for {$monthKey}: " . $invalidPeriods->take(10)->implode(', '));
                        }

                        $specifiedSum = (float) $items->pluck('ratio')->filter(fn($v) => $v !== null)->sum();
                        if ($specifiedSum > 1.001) {
                            throw new \RuntimeException("Invalid ratio for {$monthKey}. Total specified ratio exceeds 1.0 (now {$specifiedSum}).");
                        }

                        $unsetCount = (int) $items->filter(fn($r) => $r['ratio'] === null)->count();
                        if ($unsetCount === 0) {
                            if (abs($specifiedSum - 1.0) > 0.001) {
                                throw new \RuntimeException("Invalid ratio for {$monthKey}. Total ratio must be 1.0 (now {$specifiedSum}).");
                            }
                            return $items;
                        }

                        $remaining = 1.0 - $specifiedSum;
                        if ($remaining <= 0) {
                            throw new \RuntimeException("Invalid ratio for {$monthKey}. Remaining ratio is {$remaining} (check blank ratios).");
                        }
                        $even = $remaining / $unsetCount;

                        return $items->map(function ($r) use ($even) {
                            if ($r['ratio'] === null) {
                                $r['ratio'] = $even;
                            }
                            return $r;
                        })->values();
                    });
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            $allMonthHeaders = $originalMonthly->flatMap(fn($r) => array_keys($r['months'] ?? []))->unique()->values();
            $missingMonths = $allMonthHeaders->filter(fn($m) => !$weekMap->has($m))->values();
            if ($missingMonths->isNotEmpty()) {
                return back()->with('error', 'Week mapping missing for month columns: ' . $missingMonths->take(10)->implode(', ') . ($missingMonths->count() > 10 ? ' ...' : ''));
            }

            // weekMap builder already validates & normalizes ratios per month.

            $byKey = [];
            foreach ($originalMonthly as $row) {
                $customerPartNo = $row['customer_part_no'];
                foreach (($row['months'] ?? []) as $monthHeader => $qty) {
                    $qty = (float) $qty;
                    if ($qty <= 0) {
                        continue;
                    }
                    foreach ($weekMap[$monthHeader] as $wm) {
                        $minggu = $wm['minggu'];
                        $alloc = $qty * (float) $wm['ratio'];
                        $key = $customerPartNo . '|' . $minggu;
                        $byKey[$key] = ($byKey[$key] ?? 0) + $alloc;
                    }
                }
            }
            $normalizedRows = collect($byKey)->map(function ($qty, $key) {
                [$customerPartNo, $minggu] = explode('|', $key, 2);
                return [
                    'customer_part_no' => $customerPartNo,
                    'minggu' => $minggu,
                    'qty' => round((float) $qty, 3),
                ];
            })->values();
        }

        $totalRows = 0;
        $accepted = 0;
        $rejected = 0;

        DB::transaction(function () use ($validated, $fileName, $normalizedRows, &$totalRows, &$accepted, &$rejected, $request) {
            $import = CustomerPlanningImport::create([
                'customer_id' => (int) $validated['customer_id'],
                'file_name' => $fileName,
                'uploaded_by' => $request->user()?->id,
                'status' => 'completed',
            ]);

            foreach ($normalizedRows as $row) {
                $totalRows++;
                $customerPartNoRaw = str_replace("\u{00A0}", ' ', (string) ($row['customer_part_no'] ?? $row['customer_part'] ?? ''));
                $customerPartNoRaw = preg_replace('/\s+/', ' ', $customerPartNoRaw) ?? $customerPartNoRaw;
                $customerPartNo = strtoupper(trim($customerPartNoRaw));
                $minggu = strtoupper(trim((string) ($row['minggu'] ?? $row['week'] ?? '')));
                $qtyRaw = $row['qty'] ?? $row['quantity'] ?? null;
                $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : null;

                $status = 'accepted';
                $error = null;
                $partId = null;

                if ($customerPartNo === '' || !$this->validatePeriod($minggu) || $qty === null) {
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
                        $partId = $mapping->components()->value('gci_part_id');
                    }
                }

                CustomerPlanningRow::create([
                    'import_id' => $import->id,
                    'customer_part_no' => $customerPartNo,
                    'period' => $minggu, // Storing as 'period' instead of 'minggu'
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

    public function templateMonthly()
    {
        $filename = 'customer_planning_monthly_template_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new CustomerPlanningMonthlyTemplateExport(), $filename);
    }

    public function export(CustomerPlanningImport $import)
    {
        $filenameSafe = preg_replace('/[^A-Za-z0-9_.-]+/', '-', (string) ($import->file_name ?? ('import_' . $import->id)));
        $filename = 'customer_planning_import_' . $import->id . '_' . $filenameSafe . '.xlsx';

        return Excel::download(new CustomerPlanningImportRowsExport((int) $import->id), $filename);
    }
}
