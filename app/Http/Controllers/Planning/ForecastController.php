<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\GciPart;
use App\Services\Planning\ForecastGenerator;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    private function validateMinggu(string $field = 'minggu'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/']];
    }

    public function index(Request $request)
    {
        $minggu = $request->query('minggu');
        $partId = $request->query('part_id');

        $parts = GciPart::query()->orderBy('part_no')->get();

        $forecasts = Forecast::query()
            ->with('part')
            ->when($minggu, fn ($q) => $q->where('minggu', $minggu))
            ->whereHas('part')
            ->when($partId, fn ($q) => $q->where('part_id', $partId))
            ->orderBy('minggu')
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'forecasts.part_id'))
            ->paginate(100)
            ->withQueryString();

        return view('planning.forecasts.index', compact('parts', 'forecasts', 'minggu', 'partId'));
    }

    public function preview(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        // Get all Customer POs for this week
        $customerPos = \App\Models\CustomerPo::query()
            ->with(['customerPart.gciPart', 'customer'])
            ->where('minggu', $minggu)
            ->where('status', 'open')
            ->whereNotNull('part_id')
            ->orderBy('id')
            ->get();

        // Get all Planning Rows for this week
        $planningRows = \App\Models\CustomerPlanningRow::query()
            ->with(['import.customer', 'customerPart.gciPart', 'customerPart.components.part'])
            ->where('minggu', $minggu)
            ->where('row_status', 'accepted')
            ->orderBy('id')
            ->get();

        return view('planning.forecasts.preview', compact('minggu', 'customerPos', 'planningRows'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'minggu' => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/'],
            'selected_pos' => 'nullable|array',
            'selected_pos.*' => 'exists:customer_pos,id',
            'selected_planning' => 'nullable|array',
            'selected_planning.*' => 'exists:customer_planning_rows,id',
        ]);
        
        $minggu = $validated['minggu'];
        $selectedPoIds = $validated['selected_pos'] ?? [];
        $selectedPlanningIds = $validated['selected_planning'] ?? [];

        // If no selection, use all data (backward compatibility)
        if (empty($selectedPoIds) && empty($selectedPlanningIds)) {
            app(ForecastGenerator::class)->generateForWeek($minggu);
        } else {
            app(ForecastGenerator::class)->generateFromSelected($minggu, $selectedPoIds, $selectedPlanningIds);
        }

        return redirect()->route('planning.forecasts.index', ['minggu' => $minggu])
            ->with('success', 'Forecast generated.');
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
