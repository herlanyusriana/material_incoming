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
                    $aggregated[$key] = [
                        'component_part_id' => $key,
                        'component_part_no' => $item['component_part_no'],
                        'component_part' => $item['component_part'],
                        'make_or_buy' => strtoupper(trim($item['make_or_buy'] ?? '')),
                        'gross_qty' => 0,
                        'uom' => $item['consumption_uom'],
                    ];
                    $fgSources[$key] = [];
                }

                $aggregated[$key]['gross_qty'] += $item['total_qty'];

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
            $stockMap = GciInventory::whereIn('gci_part_id', $componentIds)
                ->selectRaw('gci_part_id, SUM(on_hand) as total_on_hand')
                ->groupBy('gci_part_id')
                ->pluck('total_on_hand', 'gci_part_id');

            // Build final requirements collection
            foreach ($aggregated as $key => $agg) {
                $stockOnHand = $stockMap[$key] ?? 0;
                $netQty = max(0, $agg['gross_qty'] - $stockOnHand);
                $isBuyItem = in_array($agg['make_or_buy'], ['BUY', 'B', 'PURCHASE']);

                $requirements->push([
                    'component_part_id' => $agg['component_part_id'],
                    'component_part_no' => $agg['component_part_no'],
                    'component_part_name' => $agg['component_part']?->part_name ?? 'Unknown',
                    'component_classification' => $agg['component_part']?->classification ?? '',
                    'make_or_buy' => $agg['make_or_buy'] ?: 'N/A',
                    'gross_qty' => $agg['gross_qty'],
                    'stock_on_hand' => $stockOnHand,
                    'net_qty' => $netQty,
                    'uom' => $agg['uom'],
                    'status' => !$isBuyItem ? 'N/A' : ($netQty <= 0 ? 'available' : 'shortage'),
                    'fg_sources' => array_values($fgSources[$key]),
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
        ));
    }
}
