<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\CustomerPo;
use App\Models\Forecast;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForecastController extends Controller
{
    private function validateMinggu(string $field = 'minggu'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/']];
    }

    public function index(Request $request)
    {
        $minggu = $request->query('minggu') ?: now()->format('o-\\WW');
        $partId = $request->query('part_id');

        $parts = GciPart::query()->orderBy('part_no')->get();

        $forecasts = Forecast::query()
            ->with('part')
            ->where('minggu', $minggu)
            ->when($partId, fn ($q) => $q->where('part_id', $partId))
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'forecasts.part_id'))
            ->paginate(25)
            ->withQueryString();

        return view('planning.forecasts.index', compact('parts', 'forecasts', 'minggu', 'partId'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        $planningRows = DB::table('customer_planning_rows as r')
            ->join('customer_planning_imports as i', 'i.id', '=', 'r.import_id')
            ->join('customer_parts as cp', function ($join) {
                $join->on('cp.customer_id', '=', 'i.customer_id')
                    ->on('cp.customer_part_no', '=', 'r.customer_part_no');
            })
            ->join('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
            ->where('r.row_status', 'accepted')
            ->where('r.minggu', $minggu)
            ->select('cpc.part_id', DB::raw('SUM(r.qty * cpc.usage_qty) as qty'))
            ->groupBy('cpc.part_id')
            ->get();

        $planningByPart = $planningRows->pluck('qty', 'part_id')->map(fn ($v) => (float) $v)->all();

        $poDirect = CustomerPo::query()
            ->whereNotNull('part_id')
            ->where('minggu', $minggu)
            ->where('status', 'open')
            ->select('part_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('part_id')
            ->get();

        $poFromCustomerPart = DB::table('customer_pos as po')
            ->join('customer_parts as cp', function ($join) {
                $join->on('cp.customer_id', '=', 'po.customer_id')
                    ->on('cp.customer_part_no', '=', 'po.customer_part_no');
            })
            ->join('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
            ->whereNull('po.part_id')
            ->whereNotNull('po.customer_part_no')
            ->where('po.minggu', $minggu)
            ->where('po.status', 'open')
            ->select('cpc.part_id', DB::raw('SUM(po.qty * cpc.usage_qty) as qty'))
            ->groupBy('cpc.part_id')
            ->get();

        $poByPart = [];
        foreach ($poDirect as $row) {
            $poByPart[(int) $row->part_id] = ((float) $row->qty) + ($poByPart[(int) $row->part_id] ?? 0);
        }
        foreach ($poFromCustomerPart as $row) {
            $poByPart[(int) $row->part_id] = ((float) $row->qty) + ($poByPart[(int) $row->part_id] ?? 0);
        }

        $partIds = collect(array_keys($planningByPart))
            ->merge(array_keys($poByPart))
            ->unique()
            ->values();

        foreach ($partIds as $partId) {
            $planningQty = (float) ($planningByPart[$partId] ?? 0);
            $poQty = (float) ($poByPart[$partId] ?? 0);
            $forecastQty = max($planningQty, $poQty);

            $source = 'planning';
            if ($planningQty <= 0 && $poQty > 0) {
                $source = 'po';
            } elseif ($planningQty > 0 && $poQty > 0) {
                $source = 'mixed';
            }

            Forecast::updateOrCreate(
                ['part_id' => $partId, 'minggu' => $minggu],
                [
                    'qty' => $forecastQty,
                    'planning_qty' => $planningQty,
                    'po_qty' => $poQty,
                    'source' => $source,
                ],
            );
        }

        return redirect()->route('planning.forecasts.index', ['minggu' => $minggu])
            ->with('success', 'Forecast generated.');
    }
}
