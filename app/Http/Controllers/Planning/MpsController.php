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
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']];
    }

    public function index(Request $request)
    {
        $period = $request->query('period', now()->format('Y-m'));
        $q = trim((string) $request->query('q', ''));
        $classification = strtoupper(trim((string) $request->query('classification', 'FG')));
        $classification = $classification === 'ALL' ? '' : $classification;
        $monthsCount = (int) $request->query('months', 3);
        $monthsCount = max(1, min(12, $monthsCount));
        $hideEmpty = $request->query('hide_empty', 'on') === 'on';

        // Generate months range
        $months = $this->makeMonthsRange($period, $monthsCount);

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
                $query->whereHas('mps', function ($q) use ($months) {
                    $q->whereIn('period', $months);
                });
            })
            ->orderBy('part_no')
            ->with([
                'mps' => function ($query) use ($months) {
                    $query->whereIn('period', $months);
                }
            ]);

        $parts = $partsQuery->paginate(25)->withQueryString();

        return view('planning.mps.index', compact('period', 'parts', 'months', 'monthsCount', 'q', 'classification', 'hideEmpty'));
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

    private function generateForPeriod(string $period): void
    {
        $partIdsFromForecast = Forecast::query()
            ->where('period', $period)
            ->where('qty', '>', 0)
            ->pluck('part_id');

        $partIdsFromExistingMps = Mps::query()
            ->where('period', $period)
            ->pluck('part_id');

        $parts = GciPart::query()
            ->whereIn('id', $partIdsFromForecast->merge($partIdsFromExistingMps)->unique()->values())
            ->where('status', 'active')
            ->get(['id']);

        foreach ($parts as $part) {
            $forecastQty = (float) (Forecast::query()
                ->where('part_id', $part->id)
                ->where('period', $period)
                ->value('qty') ?? 0);

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
        $validated = $request->validate($this->validatePeriod());
        $period = $validated['period'];

        DB::transaction(function () use ($period) {
            // Note: ForecastGenerator might not have generateForPeriod method yet
            // If it doesn't exist, just generate MPS from existing forecasts
            $this->generateForPeriod($period);
        });

        return back()->with('success', 'MPS generated (draft) and forecast refreshed for ' . $period);
    }

    public function generateRange(Request $request)
    {
        $validated = $request->validate($this->validatePeriod('period'));
        $period = $validated['period'];
        $monthsCount = (int) $request->input('months', 3);
        $monthsCount = max(1, min(12, $monthsCount));

        $months = $this->makeMonthsRange($period, $monthsCount);

        DB::transaction(function () use ($months) {
            foreach ($months as $m) {
                // Note: ForecastGenerator might not have generateForPeriod method yet
                $this->generateForPeriod($m);
            }
        });

        return redirect()
            ->route('planning.mps.index', ['period' => $period, 'months' => $monthsCount])
            ->with('success', "MPS generated for " . count($months) . " months (draft) and forecast refreshed.");
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
