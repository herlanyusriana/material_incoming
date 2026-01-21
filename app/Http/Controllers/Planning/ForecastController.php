<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\GciPart;
use App\Services\Planning\ForecastGenerator;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']];
    }

    public function index(Request $request)
    {
        $period = $request->query('period');
        $partId = $request->query('part_id');

        $parts = GciPart::query()->orderBy('part_no')->get();

        $forecasts = Forecast::query()
            ->with('part')
            ->when($period, fn($q) => $q->where('period', $period))
            ->whereHas('part')
            ->when($partId, fn($q) => $q->where('part_id', $partId))
            ->orderBy('period')
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'forecasts.part_id'))
            ->paginate(100)
            ->withQueryString();

        return view('planning.forecasts.index', compact('parts', 'forecasts', 'period', 'partId'));
    }

    public function preview(Request $request)
    {
        $period = $request->query('period'); // Monthly period filter

        // Get all Customer POs (status open)
        $customerPos = \App\Models\CustomerPo::query()
            ->with(['part', 'customer'])
            ->where('status', 'open')
            ->whereNotNull('part_id')
            ->when($period, fn($q) => $q->where('period', $period))
            ->orderBy('period')
            ->orderBy('id')
            ->get();

        // Get all Customer Planning Imports (files) that have accepted rows
        $planningImports = \App\Models\CustomerPlanningImport::query()
            ->with(['customer'])
            ->withCount([
                'rows as accepted_rows_count' => function ($q) {
                    $q->where('row_status', 'accepted');
                }
            ])
            ->withSum([
                'rows as total_accepted_qty' => function ($q) {
                    $q->where('row_status', 'accepted');
                }
            ], 'qty')
            ->whereHas('rows', function ($q) {
                $q->where('row_status', 'accepted');
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('planning.forecasts.preview', compact('period', 'customerPos', 'planningImports'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'selected_pos' => 'nullable|array',
            'selected_pos.*' => 'exists:customer_pos,id',
            'selected_imports' => 'nullable|array',
            'selected_imports.*' => 'exists:customer_planning_imports,id',
        ]);

        $selectedPoIds = $validated['selected_pos'] ?? [];
        $selectedImportIds = $validated['selected_imports'] ?? [];

        if (empty($selectedPoIds) && empty($selectedImportIds)) {
            return redirect()->back()->with('error', 'Please select at least one item.');
        }

        app(ForecastGenerator::class)->generateFromSelected(null, $selectedPoIds, [], $selectedImportIds);

        return redirect()->route('planning.forecasts.index')
            ->with('success', 'Forecast generated from selected sources.');
    }

    /**
     * Clear all Forecast data
     */
    public function clear(Request $request)
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            $count = Forecast::count();

            Forecast::query()->delete();

            // Log the clear action
            \App\Models\ForecastHistory::create([
                'user_id' => auth()->id(),
                'action' => 'clear',
                'parts_count' => $count,
                'notes' => 'Cleared all forecast data',
            ]);
        });

        return redirect()->route('planning.forecasts.index')->with('success', 'All forecast data has been cleared.');
    }

    /**
     * Show Forecast history
     */
    public function history(Request $request)
    {
        $histories = \App\Models\ForecastHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('planning.forecasts.history', compact('histories'));
    }
}
