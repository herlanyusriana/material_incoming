<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\Mps;
use App\Models\GciPart;
use App\Services\Planning\ForecastGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpsController extends Controller
{
    private function validateMinggu(string $field = 'minggu'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/']];
    }

    public function index(Request $request)
    {
        $minggu = $request->query('minggu'); // Optional
        $view = $request->query('view', 'calendar');
        $q = trim((string) $request->query('q', ''));
        $classification = strtoupper(trim((string) $request->query('classification', 'FG')));
        $classification = $classification === 'ALL' ? '' : $classification;
        $weeksCount = (int) $request->query('weeks', 4);
        $weeksCount = max(1, min(52, $weeksCount));

        if ($view === 'monthly') {
            // Start Month
            $startMonth = $minggu ?: now()->format('Y-m'); // YYYY-MM
            
            // Generate next X months
            $months = [];
            $date = Carbon::parse($startMonth . '-01');
            $monthsCount = max(1, min(24, $weeksCount)); // Reuse variable for count
            
            for ($i = 0; $i < $monthsCount; $i++) {
                $months[] = $date->copy()->addMonths($i)->format('Y-m');
            }
            
            // For query, we need all weeks belonging to these months
            $allWeeks = [];
            foreach ($months as $m) {
                $allWeeks = array_merge($allWeeks, $this->getWeeksForMonth($m));
            }
            $allWeeks = array_unique($allWeeks);

            $parts = GciPart::query()
                ->where('status', 'active')
                ->when($classification !== '', fn ($q) => $q->where('classification', $classification))
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('part_no', 'like', '%' . $q . '%')
                            ->orWhere('part_name', 'like', '%' . $q . '%');
                    });
                })
                ->orderBy('part_no')
                ->with(['mps' => function ($query) use ($allWeeks) {
                    $query->whereIn('minggu', $allWeeks);
                }])
                ->paginate(25)
                ->withQueryString();

            return view('planning.mps.index', compact('minggu', 'parts', 'view', 'months', 'weeksCount', 'q', 'classification', 'hideEmpty'));
        }

        // For calendar view (Weekly), we need a start week. Default to now if not provided.
        $startMinggu = $minggu ?: now()->format('o-\\WW');
        $weeks = $this->makeWeeksRange($startMinggu, $weeksCount);
        $hideEmpty = $request->query('hide_empty', 'on') === 'on';


        if ($view === 'list') {
            $rows = Mps::query()
                ->with('part')
                ->when($minggu, fn ($q) => $q->where('minggu', $minggu)) // Filter only if specific week requested
                ->when($classification !== '', fn ($q) => $q->whereHas('part', fn ($p) => $p->where('classification', $classification)))
                ->orderBy('minggu')
                ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'mps.part_id'))
                ->get(); // Using get() as requested to "show all", be careful with volume

            return view('planning.mps.index', compact('minggu', 'rows', 'view', 'weeks', 'weeksCount', 'q', 'classification', 'hideEmpty'));
        }

        $parts = GciPart::query()
            ->where('status', 'active')
            ->when($classification !== '', fn ($q) => $q->where('classification', $classification))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%');
                });
            })
            ->when($hideEmpty && $view === 'calendar', function ($query) use ($weeks) {
                $query->whereHas('mps', function ($q) use ($weeks) {
                    $q->whereIn('minggu', $weeks);
                });
            })
            ->orderBy('part_no')
            ->with(['mps' => function ($query) use ($weeks) {
                $query->whereIn('minggu', $weeks);
            }])
            ->paginate(25)
            ->withQueryString();

        return view('planning.mps.index', compact('minggu', 'parts', 'view', 'weeks', 'weeksCount', 'q', 'classification', 'hideEmpty'));
    }

    private function makeWeeksRange(string $startMinggu, int $weeksCount): array
    {
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $startMinggu, $m)) {
            return [$startMinggu];
        }

        $year = (int) $m[1];
        $week = (int) $m[2];
        $date = Carbon::now()->startOfDay()->setISODate($year, $week, 1);

        $weeks = [];
        for ($i = 0; $i < $weeksCount; $i++) {
            $weeks[] = $date->copy()->addWeeks($i)->format('o-\\WW');
        }

        return $weeks;
    }

    private function getWeeksForMonth(string $monthStr): array
    {
        // monthStr = YYYY-MM
        $startOfMonth = Carbon::parse($monthStr . '-01')->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        $weeks = [];
        // Iterate weeks. A week belongs to a month if its Thursday is in that month (ISO-8601 standard mostly)
        // Or simpler: If the Monday is in the month.
        // Let's stick to: All weeks starting in this month.
        
        $current = $startOfMonth->copy()->startOfWeek();
        if ($current->month != $startOfMonth->month && $current->copy()->endOfWeek()->month != $startOfMonth->month) {
             // If the week is entirely in prev month (unlikely if we start from week start of 1st day)
             // Actually, 1st day might be Wednesday. Start of week is Monday (prev month).
             // Let's use: Week string of the 1st, then add weeks until we cross month.
        }
        
        // Better approach:
        // Get Week of 1st day.
        // Get Week of Last day.
        // Range.
        
        $startWeek = $startOfMonth->isoWeekYear . '-W' . str_pad($startOfMonth->isoWeek, 2, '0', STR_PAD_LEFT);
        
        // We iterate purely by date to stay safe
        $iter = $startOfMonth->copy();
        while ($iter->month == $startOfMonth->month) {
             $w = $iter->format('o-\\WW');
             if (!in_array($w, $weeks)) {
                 $weeks[] = $w;
             }
             $iter->addWeek();
        }
        
        return $weeks;
    }

    private function generateForWeek(string $minggu): void
    {
        $partIdsFromForecast = Forecast::query()
            ->where('minggu', $minggu)
            ->where('qty', '>', 0)
            ->pluck('part_id');

        $partIdsFromExistingMps = Mps::query()
            ->where('minggu', $minggu)
            ->pluck('part_id');

        $parts = GciPart::query()
            ->whereIn('id', $partIdsFromForecast->merge($partIdsFromExistingMps)->unique()->values())
            ->where('status', 'active')
            ->get(['id']);

        foreach ($parts as $part) {
            $forecastQty = (float) (Forecast::query()
                ->where('part_id', $part->id)
                ->where('minggu', $minggu)
                ->value('qty') ?? 0);

            $existing = Mps::query()
                ->where('part_id', $part->id)
                ->where('minggu', $minggu)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === 'approved') {
                continue;
            }

            if (!$existing) {
                Mps::create([
                    'part_id' => $part->id,
                    'minggu' => $minggu,
                    'forecast_qty' => $forecastQty,
                    'open_order_qty' => 0,
                    'planned_qty' => $forecastQty,
                    'status' => 'draft',
                ]);
                continue;
            }

            $existingPlanned = (float) $existing->planned_qty;
            $existing->update([
                'forecast_qty' => $forecastQty,
                'open_order_qty' => 0,
                'planned_qty' => max($existingPlanned, $forecastQty),
            ]);
        }
    }

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        DB::transaction(function () use ($minggu) {
            app(ForecastGenerator::class)->generateForWeek($minggu);
            $this->generateForWeek($minggu);
        });

        return back()->with('success', 'MPS generated (draft) and forecast refreshed.');
    }

    public function generateRange(Request $request)
    {
        $validated = $request->validate($this->validateMinggu('minggu'));
        $minggu = $validated['minggu'];
        $weeksCount = (int) $request->input('weeks', 4);
        $weeksCount = max(1, min(12, $weeksCount));

        $weeks = $this->makeWeeksRange($minggu, $weeksCount);

        DB::transaction(function () use ($weeks) {
            foreach ($weeks as $w) {
                app(ForecastGenerator::class)->generateForWeek($w);
                $this->generateForWeek($w);
            }
        });

        return redirect()
            ->route('planning.mps.index', ['minggu' => $minggu, 'view' => 'calendar', 'weeks' => $weeksCount])
            ->with('success', "MPS generated for " . count($weeks) . " weeks (draft) and forecast refreshed.");
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
                'part_id' => ['required', 'exists:gci_parts,id'],
                'planned_qty' => ['required', 'numeric', 'min:0'],
                'minggu' => ['required', 'string'], // No strict regex here, we check format manually
        ]);

        $partId = (int) $validated['part_id'];
        $period = $validated['minggu'];
        $plannedQty = (float) $validated['planned_qty'];

        // Check if Monthly (YYYY-MM) or Weekly (YYYY-Wxx)
        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            // Is Monthly
            $weeks = $this->getWeeksForMonth($period);
            $count = count($weeks);
            if ($count === 0) return back()->with('error', 'No weeks found in this month.');
            
            $qtyPerWeek = $plannedQty / $count;
            
            DB::transaction(function () use ($partId, $weeks, $qtyPerWeek) {
                foreach ($weeks as $week) {
                     $this->upsertSingle($partId, $week, $qtyPerWeek);
                }
            });
            
            return back()->with('success', 'Monthly plan distributed to ' . $count . ' weeks.');
        } else {
            // Assume Weekly
            try {
                DB::transaction(fn() => $this->upsertSingle($partId, $period, $plannedQty));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
            return back()->with('success', 'MPS saved.');
        }
    }

    private function upsertSingle($partId, $minggu, $plannedQty)
    {
        $forecastQty = (float) (Forecast::query()
            ->where('part_id', $partId)
            ->where('minggu', $minggu)
            ->value('qty') ?? 0);

        $existing = Mps::query()
            ->where('part_id', $partId)
            ->where('minggu', $minggu)
            ->lockForUpdate()
            ->first();

        if ($existing && $existing->status === 'approved') {
            // converting exception to just return/continue if bulk? 
            // For monthly, if one week is approved, we might fail all or skip. 
            // Let's fail for safety.
            throw new \RuntimeException("Week $minggu is already approved.");
        }

        if (!$existing) {
            Mps::create([
                'part_id' => $partId,
                'minggu' => $minggu,
                'forecast_qty' => $forecastQty,
                'open_order_qty' => 0,
                'planned_qty' => $plannedQty,
                'status' => 'draft',
            ]);
            return;
        }

        $existing->update([
            'forecast_qty' => $forecastQty,
            'open_order_qty' => 0,
            'planned_qty' => $plannedQty,
        ]);
    }

    public function update(Request $request, Mps $mps)
    {
        if ($mps->status === 'approved') {
            return back()->with('error', 'MPS already approved.');
        }

        $validated = $request->validate([
            'planned_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $mps->update(['planned_qty' => $validated['planned_qty']]);

        return back()->with('success', 'MPS updated.');
    }

    public function approve(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        $mpsIds = collect($request->input('mps_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($mpsIds)) {
            return back()->with('error', 'Select at least one MPS row to approve.');
        }

        $updated = Mps::query()
            ->where('minggu', $minggu)
            ->where('status', 'draft')
            ->whereIn('id', $mpsIds)
            ->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        return back()->with('success', "Approved {$updated} rows.");
    }
    public function detail(Request $request)
    {
        $validated = $request->validate([
            'part_id' => 'required|exists:gci_parts,id',
            'minggu' => 'required|string',
        ]);
        
        $part = GciPart::with(['boms.items.componentPart'])->findOrFail($validated['part_id']);
        $minggu = $validated['minggu'];
        $isMonthly = preg_match('/^\d{4}-\d{2}$/', $minggu);

        if ($isMonthly) {
            $weeks = $this->getWeeksForMonth($minggu);
            $mpsCollection = Mps::where('part_id', $part->id)
                ->whereIn('minggu', $weeks)
                ->get();
                
            $plannedQty = $mpsCollection->sum('planned_qty');
            $forecastQty = Forecast::where('part_id', $part->id)
                ->whereIn('minggu', $weeks)
                ->sum('qty');
                
            $status = $mpsCollection->contains('status', 'approved') ? 'approved' : 'draft'; // If partial approved? simplified.
            
            // Create a virtual MPS object for the view
            $mps = new Mps([
                'part_id' => $part->id,
                'minggu' => $minggu, // YYYY-MM
                'forecast_qty' => $forecastQty,
                'planned_qty' => $plannedQty,
                'status' => $status,
            ]);
            $mps->setRelation('part', $part);
            $mps->exists = $mpsCollection->isNotEmpty(); // Pseudo exists
        } else {
            $mps = Mps::where('part_id', $part->id)
                ->where('minggu', $minggu)
                ->first();

            // If not exists, use Forecast info to initiate
            if (!$mps) {
                $forecastQty = (float) Forecast::where('part_id', $part->id)
                    ->where('minggu', $minggu)
                    ->value('qty');
                    
                $mps = new Mps([
                    'part_id' => $part->id,
                    'minggu' => $minggu,
                    'forecast_qty' => $forecastQty,
                    'planned_qty' => $forecastQty, // Default to forecast
                    'status' => 'draft',
                ]);
                $mps->setRelation('part', $part);
            } else {
                 $mps->load('part');
            }
        }

        return view('planning.mps.partials.detail_content', compact('mps', 'part', 'minggu'));
    }
}
