<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Imports\ForecastPlanImport;
use App\Models\Forecast;
use App\Models\ForecastDocument;
use App\Models\ForecastDocumentRow;
use App\Models\GciPart;
use App\Models\ForecastHistory;
use App\Support\PartFamily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        $family = $request->query('family');

        $parts = GciPart::query()->orderBy('part_no')->get();

        // Base query unifies period, part, and family filters AGNOSTIC of pagination so the
        // family filter is applied in SQL (via a resolved set of gci_parts.id) and rows stay
        // correct across pages. A family is a PHP-derived attribute from part_name, so we map
        // the selected family down to the matching part ids first, then filter with whereIn.
        $base = Forecast::query()
            ->with('part')
            ->whereHas('part')
            ->when($period, fn($q) => $q->where('period', $period))
            ->when($partId, fn($q) => $q->where('part_id', $partId))
            ->when($family, function ($q) use ($family) {
                $ids = GciPart::query()->get()
                    ->filter(fn($p) => PartFamily::from($p->part_name ?? null) === $family)
                    ->pluck('id');
                $q->whereIn('part_id', $ids);
            });

        // KPI totals reflect the filtered set (before pagination).
        $kpi = (clone $base)
            ->selectRaw(
                'count(*) as total_rows,
                 coalesce(sum(planning_qty),0) as total_planning,
                 coalesce(sum(po_qty),0) as total_po,
                 coalesce(sum(qty),0) as total_qty'
            )
            ->first();

        $forecasts = $base
            ->orderBy('period')
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'forecasts.part_id'))
            ->paginate(50)
            ->withQueryString();

        // Family is still a derived attribute, so attach it here for display only — the
        // filtering already happened in SQL, so this no longer affects pagination.
        $forecasts->getCollection()->transform(fn($f) => $f->setAttribute('family', PartFamily::from($f->part->part_name ?? null)));

        // NON LG is never produced by PartFamily (it falls back to Small Part), so drop it
        // from the forecast dropdown. Leave PartFamily::labels() untouched — MRP uses it too.
        $families = collect(PartFamily::labels())
            ->reject(fn($f) => $f === 'NON LG')
            ->values();

        return view('planning.forecasts.index', compact('parts', 'forecasts', 'kpi', 'period', 'partId', 'family', 'families'));
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
                'changed_by' => auth()->user()?->name ?? auth()->user()?->username ?? 'system',
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
        $histories = \App\Models\ForecastHistory::query()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('planning.forecasts.history', compact('histories'));
    }

    /**
     * Upload a PT LG plan Excel, parse it, store as a preview document, and
     * show the preview (mapped + unmapped rows) for confirmation.
     */
    public function previewPlan(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $import = new ForecastPlanImport();
            Excel::import($import, $validated['file']);

            if (!empty($import->failures)) {
                return back()->with('error', implode(' ; ', $import->failures));
            }

            $periods = $import->periods;
            $rows = $import->rows;

            if (empty($rows)) {
                return back()->with('error', 'Tidak ada baris data yang bisa diproses. Periksa format file.');
            }

            $document = DB::transaction(function () use ($import, $rows, $periods) {
                $doc = ForecastDocument::create([
                    'document_no'   => $this->nextDocumentNo(),
                    'source'        => 'lG_plan',
                    'period_start'  => $periods[0] ?? null,
                    'period_end'    => $periods[count($periods) - 1] ?? null,
                    'uploaded_by'   => auth()->id(),
                    'uploaded_at'   => now(),
                    'status'        => 'preview',
                    'total_rows'    => count($rows),
                    'mapped_rows'   => count($rows) - count($import->unmapped),
                    'unmapped_rows' => count($import->unmapped),
                ]);

                foreach ($rows as $row) {
                    ForecastDocumentRow::create([
                        'forecast_document_id' => $doc->id,
                        'customer_part_no'     => $row['customer_part_no'],
                        'gci_part_id'          => $row['gci_part_id'],
                        'mapping_status'       => $row['mapping_status'],
                        'row_no'               => $row['row_no'],
                        'quantities'           => $row['quantities'],
                    ]);
                }

                return $doc->load('rows.gciPart');
            });

            return view('planning.forecasts.preview', [
                'document' => $document,
                'periods'  => $periods,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Commit a preview document's rows into the Forecast table.
     * Only successfully mapped rows are written; unmapped rows are skipped.
     */
    public function confirmPlan(Request $request, ForecastDocument $document)
    {
        if ($document->status !== 'preview') {
            return redirect()->route('planning.forecasts.index')->with('error', 'Dokumen sudah diproses.');
        }

        $mappedRows = $document->rows()->where('mapping_status', 'mapped')->get();

        if ($mappedRows->isEmpty()) {
            return back()->with('error', 'Tidak ada baris yang ter-map. Tidak ada data di-commit.');
        }

        DB::transaction(function () use ($mappedRows, $document) {
            foreach ($mappedRows as $row) {
                foreach ($row->quantities ?? [] as $period => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }

                    $forecast = Forecast::firstOrNew([
                        'part_id' => $row->gci_part_id,
                        'period'  => $period,
                    ]);

                    // planning_qty = qty dari PLAN; qty = max(planning_qty, po_qty)
                    $forecast->planning_qty = (float) $qty;
                    $currentPoQty = (float) ($forecast->po_qty ?? 0);
                    $forecast->qty = max((float) $qty, $currentPoQty);
                    $forecast->source = 'lG_plan';
                    $forecast->save();
                }
            }

            $document->update(['status' => 'committed']);

            \App\Models\ForecastHistory::create([
                'changed_by'   => auth()->user()?->name ?? auth()->user()?->username ?? 'system',
                'action'       => 'commit_plan',
                'parts_count'  => $mappedRows->count(),
                'notes'        => "Commit plan {$document->document_no} ({$document->period_start} - {$document->period_end})",
            ]);
        });

        return redirect()->route('planning.forecasts.index')
            ->with('success', "Plan {$document->document_no} di-commit. {$mappedRows->count()} baris masuk ke Forecast.");
    }

    private function nextDocumentNo(): string
    {
        return 'PLAN-' . now()->format('YmdHis');
    }
}
