<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\GciInventory;
use App\Models\ProductionPlanningSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MaterialRequirementController extends Controller
{
    public function index(Request $request)
    {
        $planDate = Carbon::parse($request->query('date', today()->format('Y-m-d')));
        $sortBy = $request->query('sort_by', 'component');
        $sortDir = $request->query('sort_dir', 'asc');
        $calcMode = strtolower((string) $request->query('calc_mode', 'with_substitute'));
        if (!in_array($calcMode, ['strict', 'with_substitute'], true)) {
            $calcMode = 'with_substitute';
        }

        $session = ProductionPlanningSession::where('plan_date', $planDate->format('Y-m-d'))->first();

        $requirements = collect();

        if ($session) {
            $lines = $session->lines()
                ->with('gciPart')
                ->where('plan_qty', '>', 0)
                ->get();

            // Explode BOM for each planning line
            $rawExplosion = [];
            foreach ($lines as $line) {
                $bom = Bom::activeVersion($line->gci_part_id);
                if (!$bom) {
                    continue;
                }

                $explosion = $bom->explode($line->plan_qty);
                foreach ($explosion as $item) {
                    $rawExplosion[] = array_merge($item, [
                        'fg_part' => $line->gciPart,
                        'fg_plan_qty' => $line->plan_qty,
                    ]);
                }
            }

            // Aggregate by component_part_id — sum gross qty across all FG parents
            $aggregated = [];
            $fgSources = []; // track which FG parts use each component
            foreach ($rawExplosion as $item) {
                $key = $item['component_part_id'];
                if (!$key) {
                    continue;
                }

                if (!isset($aggregated[$key])) {
                    $componentPartNo = trim((string) ($item['component_part_no'] ?? ''));
                    if ($componentPartNo === '') {
                        $componentPartNo = trim((string) ($item['component_part']?->part_no ?? ''));
                    }
                    $aggregated[$key] = [
                        'component_part_id' => $key,
                        'component_part_no' => $componentPartNo !== '' ? $componentPartNo : '-',
                        'component_part' => $item['component_part'],
                        'make_or_buy' => strtoupper(trim($item['make_or_buy'] ?? '')),
                        'gross_qty' => 0,
                        'uom' => $item['consumption_uom'],
                        'substitutes' => [],
                    ];
                    $fgSources[$key] = [];
                }

                $aggregated[$key]['gross_qty'] += $item['total_qty'];

                $bomItem = $item['bom_item'] ?? null;
                if ($bomItem && $bomItem->relationLoaded('substitutes')) {
                    foreach (($bomItem->substitutes ?? collect()) as $sub) {
                        if (($sub->status ?? 'active') !== 'active') {
                            continue;
                        }
                        if (!$sub->substitute_part_id) {
                            continue;
                        }
                        $subKey = (int) $sub->substitute_part_id;
                        if (!isset($aggregated[$key]['substitutes'][$subKey])) {
                            $aggregated[$key]['substitutes'][$subKey] = [
                                'part_id' => $subKey,
                                'part_no' => $sub->part?->part_no ?? $sub->substitute_part_no ?? '-',
                                'part_name' => $sub->part?->part_name ?? '',
                                'ratio' => (float) ($sub->ratio ?? 1),
                                'priority' => (int) ($sub->priority ?? 1),
                            ];
                        }
                    }
                }

                // Track FG sources (unique by part id)
                $fgId = $item['fg_part']->id;
                if (!isset($fgSources[$key][$fgId])) {
                    $fgSources[$key][$fgId] = [
                        'part' => $item['fg_part'],
                        'plan_qty' => $item['fg_plan_qty'],
                    ];
                }
            }

            // Load stock on hand for all component parts
            $componentIds = array_keys($aggregated);
            $substituteIds = collect($aggregated)
                ->flatMap(fn($a) => array_keys($a['substitutes'] ?? []))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $allStockIds = collect(array_merge($componentIds, $substituteIds))
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $stockMap = GciInventory::whereIn('gci_part_id', $allStockIds)
                ->selectRaw('gci_part_id, SUM(on_hand) as total_on_hand')
                ->groupBy('gci_part_id')
                ->pluck('total_on_hand', 'gci_part_id');

            // Build final requirements collection
            foreach ($aggregated as $key => $agg) {
                $stockOnHand = (float) ($stockMap[$key] ?? 0);
                $substituteDetails = collect($agg['substitutes'] ?? [])
                    ->sortBy('priority')
                    ->map(function ($sub) use ($stockMap) {
                        $sub['stock_on_hand'] = (float) ($stockMap[$sub['part_id']] ?? 0);
                        return $sub;
                    })
                    ->values();
                $substituteStock = (float) $substituteDetails->sum('stock_on_hand');
                $totalStock = $stockOnHand + $substituteStock;
                $effectiveStock = $calcMode === 'strict' ? $stockOnHand : $totalStock;
                $netQty = max(0, $agg['gross_qty'] - $effectiveStock);
                $isBuyItem = in_array($agg['make_or_buy'], ['BUY', 'B', 'PURCHASE']);

                $requirements->push([
                    'component_part_id' => $agg['component_part_id'],
                    'component_part_no' => $agg['component_part_no'],
                    'component_part_name' => $agg['component_part']?->part_name ?? 'Unknown',
                    'component_classification' => $agg['component_part']?->classification ?? '',
                    'make_or_buy' => $agg['make_or_buy'] ?: 'N/A',
                    'gross_qty' => $agg['gross_qty'],
                    'stock_on_hand' => $stockOnHand,
                    'substitute_stock' => $substituteStock,
                    'total_stock_on_hand' => $totalStock,
                    'effective_stock_on_hand' => $effectiveStock,
                    'net_qty' => $netQty,
                    'uom' => $agg['uom'],
                    'status' => !$isBuyItem ? 'N/A' : ($netQty <= 0 ? 'available' : 'shortage'),
                    'fg_sources' => array_values($fgSources[$key]),
                    'substitutes' => $substituteDetails->all(),
                ]);
            }

            // Sorting
            $requirements = $requirements->sortBy(function ($item) use ($sortBy) {
                return match ($sortBy) {
                    'component' => $item['component_part_no'],
                    'type' => $item['make_or_buy'],
                    'status' => $item['status'] === 'shortage' ? 0 : 1,
                    'net_qty' => -$item['net_qty'],
                    default => $item['component_part_no'],
                };
            }, SORT_REGULAR, $sortDir === 'desc');

            $requirements = $requirements->values();
        }

        // Summary
        $totalComponents = $requirements->count();
        $totalShortage = $requirements->where('status', 'shortage')->count();
        $totalFgPlanned = $session ? $session->lines()->where('plan_qty', '>', 0)->count() : 0;

        return view('production.material-requirement.index', compact(
            'requirements',
            'session',
            'planDate',
            'sortBy',
            'sortDir',
            'totalComponents',
            'totalShortage',
            'totalFgPlanned',
            'calcMode',
        ));
    }
}
