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
        $weeksCount = max(1, min(12, $weeksCount));

        // For calendar view, we need a start week. Default to now if not provided.
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
        // Carbon version in this project doesn't support createFromIsoDate().
        // Use setISODate() which is available in Carbon 2.
        $date = Carbon::now()->startOfDay()->setISODate($year, $week, 1);

        $weeks = [];
        for ($i = 0; $i < $weeksCount; $i++) {
            $weeks[] = $date->copy()->addWeeks($i)->format('o-\\WW');
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
        $validated = $request->validate(array_merge(
            $this->validateMinggu(),
            [
                'part_id' => ['required', 'exists:gci_parts,id'],
                'planned_qty' => ['required', 'numeric', 'min:0'],
            ]
        ));

        $partId = (int) $validated['part_id'];
        $minggu = $validated['minggu'];
        $plannedQty = (float) $validated['planned_qty'];

        try {
            DB::transaction(function () use ($partId, $minggu, $plannedQty) {
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
                    throw new \RuntimeException('MPS already approved.');
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
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'MPS saved.');
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
}
