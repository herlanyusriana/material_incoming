<?php

namespace App\Http\Controllers;

use App\Exports\OutgoingDailyPlanningExport;
use App\Exports\OutgoingDailyPlanningTemplateExport;
use App\Exports\StockAtCustomersExport;
use App\Imports\StockAtCustomersImport;
use App\Imports\OutgoingDailyPlanningImport;
use App\Models\StockAtCustomer;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use App\Models\CustomerPart;
use App\Models\GciPart;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $search = trim((string) request('search', ''));
        $perPage = (int) request('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
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
            $totals = OutgoingDailyPlanCell::query()
                ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
                ->where('r.plan_id', $plan->id)
                ->whereBetween('outgoing_daily_plan_cells.plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw('outgoing_daily_plan_cells.plan_date as plan_date, SUM(COALESCE(outgoing_daily_plan_cells.qty, 0)) as total_qty')
                ->groupBy('outgoing_daily_plan_cells.plan_date')
                ->pluck('total_qty', 'plan_date')
                ->all();

            foreach ($totals as $k => $totalQty) {
                if (isset($totalsByDate[$k])) {
                    $totalsByDate[$k] = (int) $totalQty;
                }
            }

            $rows = $plan->rows()
                ->with([
                    'gciPart.standardPacking',
                    'cells' => function ($query) use ($dateFrom, $dateTo) {
                        $query
                            ->select(['id', 'row_id', 'plan_date', 'seq', 'qty'])
                            ->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                    },
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('production_line', 'like', '%' . $search . '%')
                            ->orWhere('part_no', 'like', '%' . $search . '%');
                    });
                })
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('outgoing.daily_planning', [
            'plan' => $plan,
            'rows' => $rows,
            'days' => $days,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalsByDate' => $totalsByDate,
            'search' => $search,
            'perPage' => $perPage,
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

        $msg = 'Daily planning berhasil diimport.';
        if (!empty($import->createdParts)) {
            $msg .= ' (Info: ' . count($import->createdParts) . ' part baru otomatis didaftarkan: ' . implode(', ', array_slice($import->createdParts, 0, 5)) . (count($import->createdParts) > 5 ? '...' : '') . ')';
        }

        return redirect()->route('outgoing.daily-planning', ['plan_id' => $plan?->id])->with('success', $msg);
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

        // Best-effort: backfill missing gci_part_id for rows that have demand in this date range.
        $rowIdsNeedingFix = OutgoingDailyPlanCell::query()
            ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
            ->whereBetween('outgoing_daily_plan_cells.plan_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('outgoing_daily_plan_cells.qty', '>', 0)
            ->whereNull('r.gci_part_id')
            ->distinct()
            ->pluck('r.id');

        if ($rowIdsNeedingFix->isNotEmpty()) {
            OutgoingDailyPlanRow::query()
                ->whereIn('id', $rowIdsNeedingFix)
                ->select(['id', 'part_no'])
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        $partNo = $this->normalizePartNo((string) ($row->part_no ?? ''));
                        if ($partNo === '') {
                            continue;
                        }
                        $resolvedId = $this->resolveFgPartIdFromPartNo($partNo);
                        if ($resolvedId) {
                            OutgoingDailyPlanRow::query()->whereKey($row->id)->update(['gci_part_id' => $resolvedId]);
                        }
                    }
                });
        }

        // Fetch cells from Daily Plans within range, filtered by quantity
        $cells = OutgoingDailyPlanCell::query()
            ->with(['row.gciPart.customer', 'row.gciPart.standardPacking'])
            ->whereBetween('plan_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->where('qty', '>', 0)
            ->get();

        $requirements = $cells->groupBy(function ($cell) {
            // Group key: Date|GciPartID (or Date|raw part_no if unmapped)
            $date = $cell->plan_date->format('Y-m-d');
            $partKey = $cell->row->gci_part_id
                ? ('gci:' . $cell->row->gci_part_id)
                : ('raw:' . (string) ($cell->row->part_no ?? ''));
            return "{$date}|{$partKey}";
        })->map(function ($group) {
            $first = $group->first();
            
            $gciPart = $first->row->gciPart ?? null;

            $totalQty = $group->sum('qty');
            $stdPacking = $gciPart?->standardPacking ?? null;
            $packQty = $stdPacking?->packing_qty ?? 1;

            $sequence = $group
                ->pluck('seq')
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->map(fn ($v) => (int) $v)
                ->min();
            
            return (object) [
                'date' => $first->plan_date,
                'customer' => $gciPart?->customer ?? null,
                'gci_part' => $gciPart,
                'customer_part_no' => $first->row->part_no,
                'unmapped' => $gciPart === null,
                'total_qty' => $totalQty,
                'sequence' => $sequence, // Minimum numeric sequence in the group
                'packing_std' => $packQty,
                'packing_load' => $packQty > 0 ? ceil($totalQty / $packQty) : 0,
                'uom' => $stdPacking?->uom ?? 'PCS',
                'source_row_ids' => $group->pluck('row_id')->unique()->values(),
            ];
        })->filter()->sort(function($a, $b) {
            // Sort by Date then Sequence
            if ($a->date->ne($b->date)) {
                return $a->date->gt($b->date) ? 1 : -1;
            }
            return ($a->sequence ?? 999) <=> ($b->sequence ?? 999);
        })->values();

        $trucks = \App\Models\Truck::query()
            ->select(['id', 'plate_no', 'type', 'capacity', 'status'])
            ->where('status', 'available')
            ->orderBy('plate_no')
            ->get();

        $drivers = \App\Models\Driver::query()
            ->select(['id', 'name', 'phone', 'license_type', 'status'])
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        return view('outgoing.delivery_requirements', compact('requirements', 'dateFrom', 'dateTo', 'trucks', 'drivers'));
    }

    public function generateSoBulk(Request $request)
    {
        $validated = $request->validate([
            'truck_id' => ['required', 'exists:trucks,id'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'min:0'],
            'lines' => ['required', 'array'],
            'lines.*.date' => ['required', 'date'],
            'lines.*.customer_id' => ['required', 'exists:customers,id'],
            'lines.*.gci_part_id' => ['required', 'exists:gci_parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $selectedIdx = collect($validated['selected'])->map(fn ($v) => (int) $v)->unique()->values();
        $lines = collect($validated['lines']);

        $selectedLines = $selectedIdx
            ->map(fn (int $i) => is_array($lines->get($i)) ? array_merge(['_idx' => $i], $lines->get($i)) : null)
            ->filter()
            ->values();

        if ($selectedLines->isEmpty()) {
            return back()->with('error', 'No lines selected.');
        }

        $dates = $selectedLines->pluck('date')->unique()->values();
        if ($dates->count() !== 1) {
            return back()->with('error', 'Please select requirements for a single date only.');
        }
        $planDate = (string) $dates->first();

        $truck = \App\Models\Truck::query()->whereKey((int) $validated['truck_id'])->where('status', 'available')->first();
        if (!$truck) {
            return back()->with('error', 'Selected truck is not available.');
        }

        $driver = \App\Models\Driver::query()->whereKey((int) $validated['driver_id'])->where('status', 'available')->first();
        if (!$driver) {
            return back()->with('error', 'Selected driver is not available.');
        }

        DB::transaction(function () use ($validated, $selectedLines, $planDate) {
            $maxSeq = \App\Models\DeliveryPlan::whereDate('plan_date', $planDate)->max('sequence') ?? 0;

            $plan = \App\Models\DeliveryPlan::create([
                'plan_date' => $planDate,
                'sequence' => $maxSeq + 1,
                'truck_id' => (int) $validated['truck_id'],
                'driver_id' => (int) $validated['driver_id'],
                'status' => 'scheduled',
            ]);

            $byCustomer = $selectedLines->groupBy('customer_id');

            foreach ($byCustomer as $customerId => $customerLines) {
                $stop = \App\Models\DeliveryStop::firstOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'customer_id' => (int) $customerId,
                    ],
                    [
                        'sequence' => ($plan->stops()->max('sequence') ?? 0) + 1,
                        'status' => 'pending',
                    ]
                );

                $dnNo = null;
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $candidate = 'DN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
                    if (!\App\Models\DeliveryNote::query()->where('dn_no', $candidate)->exists()) {
                        $dnNo = $candidate;
                        break;
                    }
                }
                $dnNo ??= 'DN-' . now()->format('YmdHis') . '-' . (string) Str::uuid();

                $dn = \App\Models\DeliveryNote::create([
                    'dn_no' => $dnNo,
                    'customer_id' => (int) $customerId,
                    'delivery_date' => $planDate,
                    'status' => 'draft',
                    'delivery_plan_id' => $plan->id,
                    'delivery_stop_id' => $stop->id,
                    'notes' => 'Generated from Delivery Requirements',
                ]);

                $items = $customerLines
                    ->groupBy('gci_part_id')
                    ->map(fn ($rows) => (float) $rows->sum('qty'));

                foreach ($items as $partId => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }
                    \App\Models\DnItem::create([
                        'dn_id' => $dn->id,
                        'gci_part_id' => (int) $partId,
                        'qty' => $qty,
                    ]);
                }
            }
        });

        return redirect()->route('outgoing.delivery-plan', ['date' => $planDate])
            ->with('success', 'Delivery Plan + Delivery Notes generated successfully.');
    }

    public function generateSo(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.gci_part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function () use ($validated) {
            $dnNo = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'DN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
                if (!\App\Models\DeliveryNote::query()->where('dn_no', $candidate)->exists()) {
                    $dnNo = $candidate;
                    break;
                }
            }
            $dnNo ??= 'DN-' . now()->format('YmdHis') . '-' . (string) Str::uuid();
            
            $dn = \App\Models\DeliveryNote::create([
                'dn_no' => $dnNo,
                'customer_id' => $validated['customer_id'],
                'delivery_date' => $validated['date'],
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $item) {
                \App\Models\DnItem::create([
                    'dn_id' => $dn->id,
                    'gci_part_id' => $item['gci_part_id'],
                    'qty' => $item['qty'],
                ]);
            }
        });

        return redirect()->route('outgoing.delivery-notes.index')->with('success', 'SO generated successfully as Delivery Note.');
    }

    public function stockAtCustomers()
    {
        $period = request('period') ?: now()->format('Y-m');
        $daysInMonth = CarbonImmutable::parse($period . '-01')->daysInMonth;
        $days = range(1, $daysInMonth);

        $records = StockAtCustomer::query()
            ->with(['customer', 'part'])
            ->where('period', $period)
            ->orderBy('customer_id')
            ->orderBy('part_no')
            ->paginate(50)
            ->withQueryString();

        return view('outgoing.stock_at_customers', compact('period', 'records', 'days'));
    }

    public function stockAtCustomersTemplate(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');
        $daysInMonth = CarbonImmutable::parse($period . '-01')->daysInMonth;
        $filename = 'stock_at_customers_template_' . $period . '.xlsx';

        return Excel::download(new class($period, $daysInMonth) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private readonly string $period, private readonly int $daysInMonth)
            {
            }

            public function array(): array
            {
                $row = ['CUSTOMER_NAME', 'PART-001', 'PART NAME', 'MODEL', 'active'];
                for ($d = 1; $d <= $this->daysInMonth; $d++) {
                    $row[] = 0;
                }
                return [$row];
            }

            public function headings(): array
            {
                $base = ['customer', 'part_no', 'part_name', 'model', 'status'];
                for ($d = 1; $d <= $this->daysInMonth; $d++) {
                    $base[] = (string) $d;
                }
                return $base;
            }
        }, $filename);
    }

    public function stockAtCustomersExport(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');
        $filename = 'stock_at_customers_' . $period . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new StockAtCustomersExport($period), $filename);
    }

    public function stockAtCustomersImport(Request $request)
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\\d{4}-\\d{2}$/'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new StockAtCustomersImport($validated['period']);
        Excel::import($import, $validated['file']);

        if (!empty($import->failures)) {
            $preview = array_slice($import->failures, 0, 10);
            $msg = implode(' ; ', $preview);
            if (count($import->failures) > 10) {
                $msg .= ' ; ... and ' . (count($import->failures) - 10) . ' more errors';
            }
            return back()->with('error', "Import selesai tapi ada error: {$msg}");
        }

        $msg = "Stock at Customers imported. {$import->rowCount} rows processed.";
        if ($import->skippedRows > 0) {
            $msg .= " {$import->skippedRows} rows skipped.";
        }

        return redirect()->route('outgoing.stock-at-customers', ['period' => $validated['period']])->with('success', $msg);
    }

    public function deliveryPlan()
    {
        $date = $this->parseDate(request('date')) ?? now()->startOfDay();

        // Prepare Data for View
        $plansDb = \App\Models\DeliveryPlan::with([
            'truck', 
            'driver', 
            'stops.customer', 
            'stops.deliveryNotes.items.part'
        ])
        ->whereDate('plan_date', $date)
        ->orderBy('sequence')
        ->get();

        // MAPPING to Front-End Structure
        $deliveryPlans = $plansDb->map(function($p) {
            return [
                'id' => 'DP' . str_pad($p->id, 3, '0', STR_PAD_LEFT),
                'sequence' => $p->sequence,
                'truckId' => $p->truck_id,
                'driverId' => $p->driver_id,
                'status' => $p->status,
                'estimatedDeparture' => $p->estimated_departure ? \Carbon\Carbon::parse($p->estimated_departure)->format('H:i') : '-',
                'estimatedReturn' => $p->estimated_return ? \Carbon\Carbon::parse($p->estimated_return)->format('H:i') : '-',
                'stops' => $p->stops->map(function($s) {
                    return [
                        'id' => $s->id,
                        'customer' => $s->customer->name,
                        'customerCode' => $s->customer->code,
                        'address' => $s->customer->address,
                        'estimatedTime' => $s->estimated_arrival_time ? \Carbon\Carbon::parse($s->estimated_arrival_time)->format('H:i') : '-',
                        'status' => $s->status,
                        'deliveryOrders' => $s->deliveryNotes->map(function($dn) {
                            return [
                                'id' => $dn->dn_no,
                                'poNumber' => $dn->dn_no, // Placeholder
                                'poDate' => $dn->delivery_date->format('Y-m-d'),
                                'products' => $dn->items->map(function($item) {
                                    return [
                                        'partNo' => $item->part->part_no ?? 'N/A',
                                        'partName' => $item->part->part_name ?? 'Unknown',
                                        'quantity' => $item->qty,
                                        'unit' => 'PCS',
                                        'weight' => '-'
                                    ];
                                })
                            ];
                        })
                    ];
                }),
                'cargo_summary' => [
                    'total_items' => $p->stops->flatMap->deliveryNotes->flatMap->items->count(),
                    'total_qty' => $p->stops->flatMap->deliveryNotes->flatMap->items->sum('qty'),
                ]
            ];
        });

        $assignedTruckIds = $plansDb->pluck('truck_id')->filter()->unique()->values();
        $trucksDb = \App\Models\Truck::query()
            ->select(['id', 'plate_no', 'type', 'capacity', 'status'])
            ->when($assignedTruckIds->isNotEmpty(), function ($query) use ($assignedTruckIds) {
                $query->whereIn('id', $assignedTruckIds)->orWhere('status', 'available');
            }, function ($query) {
                $query->where('status', 'available');
            })
            ->orderBy('plate_no')
            ->get()
            ->unique('id')
            ->values();

        $trucks = $trucksDb->map(fn($t) => [
            'id' => $t->id,
            'plateNo' => $t->plate_no,
            'type' => $t->type,
            'capacity' => $t->capacity,
            'status' => $t->status
        ]);

        $assignedDriverIds = $plansDb->pluck('driver_id')->filter()->unique()->values();
        $driversDb = \App\Models\Driver::query()
            ->select(['id', 'name', 'phone', 'license_type', 'status'])
            ->when($assignedDriverIds->isNotEmpty(), function ($query) use ($assignedDriverIds) {
                $query->whereIn('id', $assignedDriverIds)->orWhere('status', 'available');
            }, function ($query) {
                $query->where('status', 'available');
            })
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

        $drivers = $driversDb->map(fn($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'phone' => $d->phone,
            'license' => $d->license_type,
            'status' => $d->status
        ]);

        $unassignedSos = \App\Models\DeliveryNote::with(['customer', 'items.part'])
            ->whereDate('delivery_date', $date)
            ->whereNull('delivery_plan_id')
            ->whereIn('status', ['draft', 'kitting', 'ready_to_pick', 'picking', 'ready_to_ship'])
            ->get()
            ->map(function($dn) {
                return [
                    'id' => $dn->id,
                    'dn_no' => $dn->dn_no,
                    'customer' => $dn->customer->name,
                    'itemCount' => $dn->items->count(),
                    'totalQty' => $dn->items->sum('qty'),
                ];
            });

        return view('outgoing.delivery_plan', [
            'deliveryPlans' => $deliveryPlans,
            'unassignedSos' => $unassignedSos,
            'trucks' => $trucks,
            'drivers' => $drivers,
            'selectedDate' => $date->format('Y-m-d'),
        ]);
    }

    public function assignSoToPlan(Request $request)
    {
        $validated = $request->validate([
            'delivery_note_id' => 'required|exists:delivery_notes,id',
            'delivery_plan_id' => 'required|exists:delivery_plans,id',
        ]);

        $dn = \App\Models\DeliveryNote::findOrFail($validated['delivery_note_id']);
        $plan = \App\Models\DeliveryPlan::findOrFail($validated['delivery_plan_id']);

        DB::transaction(function () use ($dn, $plan) {
            // Find or create a stop for this customer in this plan
            $stop = \App\Models\DeliveryStop::firstOrCreate(
                [
                    'plan_id' => $plan->id,
                    'customer_id' => $dn->customer_id,
                ],
                [
                    'sequence' => ($plan->stops()->max('sequence') ?? 0) + 1,
                    'status' => 'pending',
                ]
            );

            $dn->update([
                'delivery_plan_id' => $plan->id,
                'delivery_stop_id' => $stop->id,
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function storeDeliveryPlan(Request $request)
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
            'truck_id' => 'nullable|exists:trucks,id',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        // Auto-generate sequence: Get max sequence for the day + 1
        $maxSeq = \App\Models\DeliveryPlan::whereDate('plan_date', $validated['plan_date'])->max('sequence') ?? 0;

        $plan = \App\Models\DeliveryPlan::create([
            'plan_date' => $validated['plan_date'],
            'sequence' => $maxSeq + 1,
            'truck_id' => $validated['truck_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'status' => 'scheduled',
        ]);

        return redirect()->route('outgoing.delivery-plan', ['date' => $validated['plan_date']])
            ->with('success', 'Delivery Plan created successfully.');
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
        
        $partNo = $this->normalizePartNo($validated['part_no']);
        $gciPartId = $partNo !== '' ? $this->resolveFgPartIdFromPartNo($partNo) : null;
        
        $row = $plan->rows()->create([
            'row_no' => (int) ($plan->rows()->max('row_no') ?? 0) + 1,
            'production_line' => $validated['production_line'],
            'part_no' => $partNo !== '' ? $partNo : $validated['part_no'],
            'gci_part_id' => $gciPartId,
        ]);
        
        return back()->with('success', 'Row added.');
    }

    private function normalizePartNo(string $value): string
    {
        $str = str_replace("\u{00A0}", ' ', (string) ($value ?? ''));
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;
        $str = strtoupper(trim($str));
        if ($str === '') {
            return '';
        }

        // Common import patterns append notes like "(REV A)" or extra tokens after spaces.
        // Canonicalize to reduce accidental duplicates during auto-mapping.
        $str = preg_replace('/\s*[\\(\\[].*$/', '', $str) ?? $str;
        $str = trim($str);
        if ($str === '') {
            return '';
        }

        if (str_contains($str, ' ')) {
            $str = (string) strtok($str, ' ');
        }

        return trim($str);
    }

    private function resolveFgPartIdFromPartNo(string $partNo): ?int
    {
        $partNo = $this->normalizePartNo($partNo);
        if ($partNo === '') {
            return null;
        }

        $gciPart = GciPart::query()
            ->where('part_no', $partNo)
            ->where('classification', 'FG')
            ->first();
        if ($gciPart) {
            return (int) $gciPart->id;
        }

        $customerPart = CustomerPart::query()
            ->where('customer_part_no', $partNo)
            ->where('status', 'active')
            ->with(['components.part' => function ($q) {
                $q->where('classification', 'FG');
            }])
            ->first();

        if ($customerPart) {
            $fgComponents = $customerPart->components
                ->filter(fn ($c) => $c->part && $c->part->classification === 'FG' && $c->gci_part_id)
                ->sortByDesc(fn ($c) => (float) ($c->qty_per_unit ?? 0))
                ->values();

            $first = $fgComponents->first();
            if ($first && $first->gci_part_id) {
                return (int) $first->gci_part_id;
            }
        }

        $created = GciPart::create([
            'part_no' => $partNo,
            'part_name' => 'AUTO-CREATED (DAILY PLAN)',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        return (int) $created->id;
    }
}
