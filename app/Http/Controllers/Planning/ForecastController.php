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

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        app(ForecastGenerator::class)->generateForWeek($minggu);

        return redirect()->route('planning.forecasts.index', ['minggu' => $minggu])
            ->with('success', 'Forecast generated.');
    }
}
