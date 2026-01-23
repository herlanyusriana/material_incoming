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
    private function validateMonthPeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']];
    }

    private function validateWeekPeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W\d{2}$/']];
    }

    private function isMonthPeriod(?string $value): bool
    {
        return $value !== null && (bool) preg_match('/^\d{4}-\d{2}$/', $value);
    }

    private function isWeekPeriod(?string $value): bool
    {
        return $value !== null && (bool) preg_match('/^\d{4}-W\d{2}$/', $value);
    }

    public function index(Request $request)
    {
        $view = $request->query('view', 'calendar');
        $period = trim((string) $request->query('period', ''));
        if ($period === '') {
            $period = $view === 'calendar' ? now()->format('o-\\WW') : now()->format('Y-m');
        }

        $q = trim((string) $request->query('q', ''));
        $classification = strtoupper(trim((string) $request->query('classification', 'FG')));
        $classification = $classification === 'ALL' ? '' : $classification;
        $weeksCount = (int) $request->query('weeks', 4);
        $weeksCount = max(1, min(52, $weeksCount));
        $hideEmpty = $request->query('hide_empty', 'on') === 'on';

        $weeks = [];
        $months = [];
        $monthWeeksMap = [];

        $periodsForQuery = [];
        if ($view === 'calendar') {
            if (!$this->isWeekPeriod($period)) {
                $period = now()->format('o-\\WW');
            }
            $weeks = $this->makeWeeksRange($period, $weeksCount);
            $periodsForQuery = $weeks;
        } elseif ($view === 'monthly') {
            if (!$this->isMonthPeriod($period)) {
                $period = now()->format('Y-m');
            }
            $months = $this->makeMonthsRange($period, min(12, $weeksCount));
            foreach ($months as $m) {
                $monthWeeksMap[$m] = $this->getWeeksForMonth($m);
            }
            $periodsForQuery = collect($monthWeeksMap)->flatten()->merge($months)->unique()->values()->all();
        }

        $partsQuery = GciPart::query()
            ->where('status', 'active')
            ->when($classification !== '', fn($q) => $q->where('classification', $classification))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%');
                });
            })
            ->when($hideEmpty, function ($query) use ($months) {
                // filled below based on view; keep query valid when periods list is empty
            })
            ->orderBy('part_no')
            ->with([
                'mps' => function ($query) use ($periodsForQuery) {
                    if (!empty($periodsForQuery)) {
                        $query->whereIn('period', $periodsForQuery);
                    }
                }
            ]);

        if ($hideEmpty && !empty($periodsForQuery)) {
            $partsQuery->whereHas('mps', function ($q) use ($periodsForQuery) {
                $q->whereIn('period', $periodsForQuery);
            });
        }

        $parts = null;
        $rows = null;
        if ($view === 'list') {
            $rows = Mps::query()
                ->with('part')
                ->when($this->isMonthPeriod($period) || $this->isWeekPeriod($period), fn($q) => $q->where('period', $period))
                ->when($classification !== '', function ($query) use ($classification) {
                    $query->whereHas('part', fn($q) => $q->where('classification', $classification));
                })
                ->when($q !== '', function ($query) use ($q) {
                    $query->whereHas('part', function ($sub) use ($q) {
                        $sub->where('part_no', 'like', '%' . $q . '%')
                            ->orWhere('part_name', 'like', '%' . $q . '%');
                    });
                })
                ->latest('id')
                ->paginate(50)
                ->withQueryString();
        } else {
            $parts = $partsQuery->paginate(25)->withQueryString();
        }

        return view('planning.mps.index', compact('view', 'period', 'parts', 'rows', 'weeks', 'weeksCount', 'months', 'monthWeeksMap', 'q', 'classification', 'hideEmpty'));
    }

    private function makeMonthsRange(string $startPeriod, int $monthsCount): array
    {
        $months = [];
        $date = Carbon::parse($startPeriod . '-01');

        for ($i = 0; $i < $monthsCount; $i++) {
            $months[] = $date->copy()->addMonths($i)->format('Y-m');
        }

        return $months;
    }

    private function makeWeeksRange(string $startWeek, int $weeksCount): array
    {
        if (!preg_match('/^(?<year>\d{4})-W(?<week>\d{2})$/', $startWeek, $m)) {
            return [];
        }

        $year = (int) $m['year'];
        $week = (int) $m['week'];
        $date = Carbon::now()->setISODate($year, $week, 1)->startOfDay();

        $weeks = [];
        for ($i = 0; $i < $weeksCount; $i++) {
            $weeks[] = $date->copy()->addWeeks($i)->format('o-\\WW');
        }
        return $weeks;
    }

    private function getWeeksForMonth(string $monthStr): array
    {
        $startOfMonth = Carbon::parse($monthStr . '-01')->startOfDay();
        $weeks = [];
        $current = $startOfMonth->copy();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        while ($current->lte($endOfMonth)) {
            $w = $current->format('o-\\WW');
            if (!in_array($w, $weeks, true)) {
                $weeks[] = $w;
            }
            $current->addDay();
        }

        return $weeks;
    }

    private function generateForWeek(string $period): void
    {
        $forecastByPart = Forecast::query()
            ->where('period', $period)
            ->where('qty', '>', 0)
            ->pluck('qty', 'part_id')
            ->map(fn($v) => (float) $v)
            ->all();

        $partIdsFromForecast = array_keys($forecastByPart);

        $partIdsFromExistingMps = Mps::query()
            ->where('period', $period)
            ->pluck('part_id');

        $parts = GciPart::query()
            ->whereIn('id', collect($partIdsFromForecast)->merge($partIdsFromExistingMps)->unique()->values())
            ->where('status', 'active')
            ->get(['id']);

        foreach ($parts as $part) {
            $forecastQty = (float) ($forecastByPart[$part->id] ?? 0);

            $existing = Mps::query()
                ->where('part_id', $part->id)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === 'approved') {
                continue;
            }

            if (!$existing) {
                Mps::create([
                    'part_id' => $part->id,
                    'period' => $period,
                    'forecast_qty' => $forecastQty,
                    'open_order_qty' => 0,
                    'planned_qty' => $forecastQty,
                    'status' => 'draft',
                ]);
                continue;
            }

            $existing->update([
                'forecast_qty' => $forecastQty,
                'open_order_qty' => 0,
                'planned_qty' => $forecastQty,
            ]);
        }
    }

    public function generate(Request $request)
    {
        $validated = $request->validate(['period' => ['required', 'string']]);
        $period = trim((string) $validated['period']);

        if ($this->isWeekPeriod($period)) {
            DB::transaction(fn () => $this->generateForWeek($period));
            return back()->with('success', 'MPS generated (draft) for ' . $period);
        }

        if ($this->isMonthPeriod($period)) {
            $weeks = $this->getWeeksForMonth($period);
            DB::transaction(function () use ($weeks) {
                foreach ($weeks as $w) {
                    $this->generateForWeek($w);
                }
            });
            return back()->with('success', 'MPS generated (draft) for ' . $period . ' (from weeks: ' . count($weeks) . ')');
        }

        return back()->with('error', 'Invalid period. Use YYYY-MM or YYYY-Www (e.g., 2026-01 or 2026-W02).');
    }

    public function generateRange(Request $request)
    {
        $validated = $request->validate(array_merge(
            $this->validateWeekPeriod('period'),
            ['weeks' => ['nullable', 'integer', 'min:1', 'max:52']]
        ));

        $startWeek = $validated['period'];
        $weeksCount = (int) ($validated['weeks'] ?? 4);
        $weeksCount = max(1, min(52, $weeksCount));

        $weeks = $this->makeWeeksRange($startWeek, $weeksCount);

        DB::transaction(function () use ($weeks) {
            foreach ($weeks as $w) {
                $this->generateForWeek($w);
            }
        });

        return redirect()
            ->route('planning.mps.index', ['view' => 'calendar', 'period' => $startWeek, 'weeks' => $weeksCount])
            ->with('success', "MPS generated for " . count($weeks) . " weeks (draft).");
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'part_id' => ['required', 'exists:gci_parts,id'],
            'planned_qty' => ['required', 'numeric', 'min:0'],
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            DB::transaction(fn() => $this->upsertSingle(
                $validated['part_id'],
                $validated['period'],
                (float) $validated['planned_qty']
            ));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'MPS saved.');
    }

    private function upsertSingle($partId, $period, $plannedQty)
    {
        $forecastQty = (float) (Forecast::query()
            ->where('part_id', $partId)
            ->where('period', $period)
            ->value('qty') ?? 0);

        $existing = Mps::query()
            ->where('part_id', $partId)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if ($existing && $existing->status === 'approved') {
            throw new \RuntimeException("Period $period is already approved.");
        }

        if (!$existing) {
            Mps::create([
                'part_id' => $partId,
                'period' => $period,
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
        $mpsIds = collect($request->input('mps_ids', []))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($mpsIds)) {
            return back()->with('error', 'Select at least one MPS row to approve.');
        }

        $updated = Mps::query()
            ->where('status', 'draft')
            ->whereIn('id', $mpsIds)
            ->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        return back()->with('success', "Approved {$updated} rows.");
    }

    public function approveMonthly(Request $request)
    {
        $keys = collect($request->input('approve_keys', []))
            ->filter(fn ($v) => is_string($v) && str_contains($v, '|'))
            ->unique()
            ->values()
            ->all();

        if ($keys === []) {
            return back()->with('error', 'Select at least one month cell to approve.');
        }

        $userId = $request->user()?->id;
        $totalUpdated = 0;

        DB::transaction(function () use ($keys, $userId, &$totalUpdated) {
            foreach ($keys as $key) {
                [$partIdRaw, $month] = explode('|', $key, 2);
                if (!is_numeric($partIdRaw) || !$this->isMonthPeriod($month)) {
                    continue;
                }

                $partId = (int) $partIdRaw;
                $weeks = $this->getWeeksForMonth($month);
                $periods = array_values(array_unique(array_merge([$month], $weeks)));

                $updated = Mps::query()
                    ->where('part_id', $partId)
                    ->whereIn('period', $periods)
                    ->where('status', 'draft')
                    ->update([
                        'status' => 'approved',
                        'approved_by' => $userId,
                        'approved_at' => now(),
                    ]);

                $totalUpdated += (int) $updated;
            }
        });

        if ($totalUpdated < 1) {
            return back()->with('error', 'No draft rows were approved (maybe already approved).');
        }

        return back()->with('success', "Approved {$totalUpdated} rows.");
    }

    public function detail(Request $request)
    {
        $validated = $request->validate([
            'part_id' => 'required|exists:gci_parts,id',
            'period' => 'required|regex:/^\d{4}-\d{2}$/',
        ]);

        $part = GciPart::with(['boms.items.componentPart'])->findOrFail($validated['part_id']);
        $period = $validated['period'];

        $mps = Mps::where('part_id', $part->id)
            ->where('period', $period)
            ->first();

        if (!$mps) {
            $forecastQty = (float) Forecast::where('part_id', $part->id)
                ->where('period', $period)
                ->value('qty');

            $mps = new Mps([
                'part_id' => $part->id,
                'period' => $period,
                'forecast_qty' => $forecastQty,
                'planned_qty' => $forecastQty,
                'status' => 'draft',
            ]);
            $mps->setRelation('part', $part);
        } else {
            $mps->load('part');
        }

        return view('planning.mps.partials.detail_content', compact('mps', 'part', 'period'));
    }

    public function clear(Request $request)
    {
        DB::transaction(function () use ($request) {
            $count = Mps::count();

            Mps::query()->delete();

            \App\Models\MpsHistory::create([
                'user_id' => auth()->id(),
                'action' => 'clear',
                'parts_count' => $count,
                'notes' => 'Cleared all MPS data',
            ]);
        });

        return redirect()->route('planning.mps.index')->with('success', 'All MPS data has been cleared.');
    }

    public function history(Request $request)
    {
        $histories = \App\Models\MpsHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('planning.mps.history', compact('histories'));
    }

    public function export(Request $request)
    {
        $period = $request->query('period', now()->format('Y-m'));
        $monthsCount = (int) $request->query('months', 3);
        $q = trim((string) $request->query('q', ''));
        $classification = strtoupper(trim((string) $request->query('classification', 'FG')));
        $classification = $classification === 'ALL' ? '' : $classification;

        $months = $this->makeMonthsRange($period, $monthsCount);

        $parts = GciPart::query()
            ->where('status', 'active')
            ->when($classification !== '', fn($q) => $q->where('classification', $classification))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('part_no')
            ->with([
                'mps' => function ($query) use ($months) {
                    $query->whereIn('period', $months);
                }
            ])
            ->get();

        $excelData = compact('period', 'parts', 'months', 'monthsCount', 'q', 'classification');
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MpsExport('exports.mps.monthly', $excelData),
            'mps_monthly_report.xlsx'
        );
    }
}
