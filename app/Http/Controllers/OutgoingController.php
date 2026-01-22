<?php

namespace App\Http\Controllers;

use App\Exports\OutgoingDailyPlanningExport;
use App\Exports\OutgoingDailyPlanningTemplateExport;
use App\Imports\OutgoingDailyPlanningImport;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class OutgoingController extends Controller
{
    public function dailyPlanning()
    {
        $dateFrom = $this->parseDate(request('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate(request('date_to')) ?? now()->addDays(4)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $planId = request('plan_id');
        $plan = null;
        if ($planId) {
            $plan = OutgoingDailyPlan::query()->whereKey($planId)->first();
        }
        if (!$plan) {
            $plan = OutgoingDailyPlan::query()
                ->whereDate('date_from', $dateFrom->toDateString())
                ->whereDate('date_to', $dateTo->toDateString())
                ->latest('id')
                ->first();
        }

        if ($plan) {
            $dateFrom = $plan->date_from->copy();
            $dateTo = $plan->date_to->copy();
        }

        $days = $this->daysBetween($dateFrom, $dateTo);

        $rows = collect();
        $totalsByDate = [];
        foreach ($days as $d) {
            $totalsByDate[$d->format('Y-m-d')] = 0;
        }

        if ($plan) {
            $rows = $plan->rows()->with('cells')->get();
            foreach ($rows as $row) {
                foreach ($row->cells as $cell) {
                    $key = $cell->plan_date->format('Y-m-d');
                    if (isset($totalsByDate[$key]) && $cell->qty !== null) {
                        $totalsByDate[$key] += (int) $cell->qty;
                    }
                }
            }
        }

        return view('outgoing.daily_planning', [
            'plan' => $plan,
            'rows' => $rows,
            'days' => $days,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalsByDate' => $totalsByDate,
        ]);
    }

    public function dailyPlanningTemplate(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->addDays(4)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $filename = 'daily_planning_template_' . $dateFrom->format('Ymd') . '_' . $dateTo->format('Ymd') . '.xlsx';
        return Excel::download(new OutgoingDailyPlanningTemplateExport($dateFrom, $dateTo), $filename);
    }

    public function dailyPlanningExport(OutgoingDailyPlan $plan)
    {
        $filename = 'daily_planning_' . $plan->date_from->format('Ymd') . '_' . $plan->date_to->format('Ymd') . '.xlsx';
        return Excel::download(new OutgoingDailyPlanningExport($plan->loadMissing('rows.cells')), $filename);
    }

    public function dailyPlanningImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new OutgoingDailyPlanningImport();
        Excel::import($import, $request->file('file'));

        if (!empty($import->failures)) {
            $msg = implode('<br>', array_slice($import->failures, 0, 10));
            if (count($import->failures) > 10) {
                $msg .= '<br>... and ' . (count($import->failures) - 10) . ' more errors.';
            }
            return back()->with('error', 'Import errors found:<br>' . $msg);
        }

        $dateFrom = $import->dateFrom();
        $dateTo = $import->dateTo();
        if (!$dateFrom || !$dateTo) {
            return back()->withErrors(['file' => 'Format Excel tidak dikenali. Pastikan ada kolom tanggal seperti "2024-01-14 Seq" dan "2024-01-14 Qty".']);
        }

        $plan = null;
        DB::transaction(function () use ($import, $dateFrom, $dateTo, &$plan) {
            $plan = OutgoingDailyPlan::create([
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'created_by' => auth()->id(),
            ]);

            foreach ($import->rows as $idx => $row) {
                $rowNo = $row['row_no'] ?? null;
                $rowModel = OutgoingDailyPlanRow::create([
                    'plan_id' => $plan->id,
                    'row_no' => $rowNo ?: ($idx + 1),
                    'production_line' => $row['production_line'],
                    'part_no' => $row['part_no'],
                    'gci_part_id' => $row['gci_part_id'],
                ]);

                foreach ($row['cells'] as $date => $cell) {
                    OutgoingDailyPlanCell::create([
                        'row_id' => $rowModel->id,
                        'plan_date' => $date,
                        'seq' => $cell['seq'],
                        'qty' => $cell['qty'],
                    ]);
                }
            }
        });

        return redirect()->route('outgoing.daily-planning', ['plan_id' => $plan?->id])->with('success', 'Daily planning berhasil diimport.');
    }

    public function customerPo()
    {
        return view('outgoing.customer_po');
    }

    public function productMapping()
    {
        return view('outgoing.product_mapping');
    }

    public function deliveryRequirements(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->addDays(6)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Fetch cells from Daily Plans within range
        // We aggregate by Date + GCI Part (or Part No if GCI Part is null)
        $cells = OutgoingDailyPlanCell::query()
            ->with(['row', 'row.gciPart'])
            ->whereBetween('plan_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->get();

        $requirements = $cells->groupBy(function ($cell) {
            // Group key: Date|GciPartID|PartNo
            $date = $cell->plan_date->format('Y-m-d');
            $partId = $cell->row->gci_part_id ?? 'null';
            $partNo = $cell->row->part_no;
            return "{$date}|{$partId}|{$partNo}";
        })->map(function ($group) {
            $first = $group->first();
            return (object) [
                'date' => $first->plan_date,
                'gci_part' => $first->row->gciPart,
                'customer_part_no' => $first->row->part_no,
                'total_qty' => $group->sum('qty'),
                'source_plans' => $group->pluck('row.plan_id')->unique()->values(),
            ];
        })->sortBy('date');

        return view('outgoing.delivery_requirements', compact('requirements', 'dateFrom', 'dateTo'));
    }

    public function gciInventory()
    {
        $inventory = \App\Models\FgInventory::with('part')->get();
        return view('outgoing.gci_inventory', compact('inventory'));
    }

    public function stockAtCustomers()
    {
        return view('outgoing.stock_at_customers');
    }

    public function deliveryPlan()
    {
        return view('outgoing.delivery_plan');
    }

    private function parseDate(?string $value): ?Carbon
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }
        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<Carbon> */
    private function daysBetween(Carbon $from, Carbon $to): array
    {
        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
            if (count($days) > 31) {
                break;
            }
        }
        return $days;
    }
    public function updateCell(Request $request)
    {
        $validated = $request->validate([
            'row_id' => 'required|exists:outgoing_daily_plan_rows,id',
            'date' => 'required|date',
            'field' => 'required|in:seq,qty',
            'value' => 'nullable',
        ]);

        $rowId = $validated['row_id'];
        $date = $validated['date'];
        $field = $validated['field'];
        $value = $validated['value'];

        $cell = OutgoingDailyPlanCell::updateOrCreate(
            ['row_id' => $rowId, 'plan_date' => $date],
            [$field => $value]
        );

        return response()->json(['success' => true, 'cell' => $cell]);
    }

    public function createPlan(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $plan = OutgoingDailyPlan::create([
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('outgoing.daily-planning', ['plan_id' => $plan->id])
            ->with('success', 'New plan created. Please add rows.');
    }
    
    public function storeRow(Request $request, OutgoingDailyPlan $plan) 
    {
        $validated = $request->validate([
            'part_no' => 'required|string',
            'production_line' => 'required|string',
        ]);
        
        // Find or create GCI Part? For now assuming manual text or simple mapping
        $gciPart = \App\Models\GciPart::where('part_no', $validated['part_no'])->first();
        
        $row = $plan->rows()->create([
            'row_no' => $plan->rows()->max('row_no') + 1,
            'production_line' => $validated['production_line'],
            'part_no' => $validated['part_no'],
            'gci_part_id' => $gciPart?->id,
        ]);
        
        return back()->with('success', 'Row added.');
    }
}
