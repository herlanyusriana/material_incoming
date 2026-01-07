<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\Mps;
use App\Models\GciPart;
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
        $minggu = $request->query('minggu') ?: now()->format('o-\\WW');

        $rows = Mps::query()
            ->with('part')
            ->where('minggu', $minggu)
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'mps.part_id'))
            ->get();

        return view('planning.mps.index', compact('minggu', 'rows'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        DB::transaction(function () use ($minggu) {
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
        });

        return redirect()->route('planning.mps.index', ['minggu' => $minggu])->with('success', 'MPS generated (draft).');
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

        return redirect()->route('planning.mps.index', ['minggu' => $minggu])->with('success', "Approved {$updated} rows.");
    }
}
