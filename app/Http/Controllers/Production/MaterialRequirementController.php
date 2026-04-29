<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\GciInventory;
use App\Models\ProductionOrder;
use App\Models\ProductionPlanningSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MaterialRequirementController extends Controller
{
    private function isRmBuyMaterial(array $material): bool
    {
        return in_array($material['make_or_buy'], ['BUY', 'B', 'PURCHASE'], true)
            && strtoupper((string) ($material['component_classification'] ?? '')) === 'RM';
    }

    public function index(Request $request)
    {
        $planDate = Carbon::parse($request->query('date', today()->format('Y-m-d')));
        $sortBy = $request->query('sort_by', 'bom');
        $sortDir = $request->query('sort_dir', 'asc');
        $calcMode = strtolower((string) $request->query('calc_mode', 'with_substitute'));
        $q = trim((string) $request->query('q', ''));

        if (!in_array($calcMode, ['strict', 'with_substitute'], true)) {
            $calcMode = 'with_substitute';
        }

        $session = ProductionPlanningSession::where('plan_date', $planDate->format('Y-m-d'))->first();

        $orders = ProductionOrder::query()
            ->with('part')
            ->whereDate('plan_date', $planDate->format('Y-m-d'))
            ->where('qty_planned', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('production_order_number', 'like', '%' . $q . '%')
                        ->orWhere('transaction_no', 'like', '%' . $q . '%')
                        ->orWhereHas('part', function ($partQuery) use ($q) {
                            $partQuery->where('part_no', 'like', '%' . $q . '%')
                                ->orWhere('part_name', 'like', '%' . $q . '%');
                        });
                });
            })
            ->orderBy('plan_date')
            ->orderBy('production_order_number')
            ->get();

        $rawByOrder = [];
        $allStockIds = collect();

        foreach ($orders as $order) {
            $bom = Bom::activeVersion($order->gci_part_id, $planDate);

            if (!$bom) {
                $rawByOrder[] = [
                    'order' => $order,
                    'has_bom' => false,
                    'materials' => collect(),
                ];
                continue;
            }

            $explosion = collect($bom->explode($order->qty_planned));
            $aggregated = [];

            foreach ($explosion as $index => $item) {
                $componentPartId = (int) ($item['component_part_id'] ?? 0);
                if ($componentPartId <= 0) {
                    continue;
                }

                if (!isset($aggregated[$componentPartId])) {
                    $componentPartNo = trim((string) ($item['component_part_no'] ?? ''));
                    if ($componentPartNo === '') {
                        $componentPartNo = trim((string) ($item['component_part']?->part_no ?? ''));
                    }

                    $componentSize = trim((string) ($item['component_part']?->size ?? $item['material_size'] ?? ''));

                    $aggregated[$componentPartId] = [
                        'component_part_id' => $componentPartId,
                        'component_part_no' => $componentPartNo !== '' ? $componentPartNo : '-',
                        'component_part_name' => (string) ($item['component_part']?->part_name ?? 'Unknown'),
                        'component_size' => $componentSize,
                        'component_classification' => (string) ($item['component_part']?->classification ?? ''),
                        'make_or_buy' => strtoupper(trim((string) ($item['make_or_buy'] ?? ''))),
                        'gross_qty' => 0,
                        'uom' => (string) ($item['consumption_uom'] ?? 'PCS'),
                        'substitutes' => [],
                        'bom_index' => $index,
                        'process_name' => (string) ($item['process_name'] ?? ''),
                    ];
                }

                $aggregated[$componentPartId]['gross_qty'] += (float) ($item['total_qty'] ?? 0);

                $bomItem = $item['bom_item'] ?? null;
                if ($bomItem && $bomItem->relationLoaded('substitutes')) {
                    foreach (($bomItem->substitutes ?? collect()) as $substitute) {
                        if (($substitute->status ?? 'active') !== 'active' || !$substitute->substitute_part_id) {
                            continue;
                        }

                        $substituteId = (int) $substitute->substitute_part_id;
                        if (!isset($aggregated[$componentPartId]['substitutes'][$substituteId])) {
                            $aggregated[$componentPartId]['substitutes'][$substituteId] = [
                                'part_id' => $substituteId,
                                'part_no' => $substitute->part?->part_no ?? $substitute->substitute_part_no ?? '-',
                                'part_name' => $substitute->part?->part_name ?? '',
                                'size' => $substitute->part?->size ?? '',
                                'priority' => (int) ($substitute->priority ?? 1),
                            ];
                        }
                    }
                }
            }

            foreach ($aggregated as $material) {
                $allStockIds->push((int) $material['component_part_id']);
                foreach (($material['substitutes'] ?? []) as $substitute) {
                    $allStockIds->push((int) ($substitute['part_id'] ?? 0));
                }
            }

            $rawByOrder[] = [
                'order' => $order,
                'has_bom' => true,
                'materials' => collect($aggregated),
            ];
        }

        $stockMap = GciInventory::query()
            ->whereIn('gci_part_id', $allStockIds->filter()->unique()->values()->all())
            ->selectRaw('gci_part_id, SUM(on_hand) as total_on_hand')
            ->groupBy('gci_part_id')
            ->pluck('total_on_hand', 'gci_part_id');

        $requirementsByOrder = collect($rawByOrder)->map(function (array $payload) use ($stockMap, $calcMode, $sortBy, $sortDir) {
            $materials = collect($payload['materials'] ?? [])
                ->map(function (array $material) use ($stockMap, $calcMode) {
                    $stockOnHand = (float) ($stockMap[$material['component_part_id']] ?? 0);

                    $substitutes = collect($material['substitutes'] ?? [])
                        ->sortBy('priority')
                        ->map(function (array $substitute) use ($stockMap) {
                            $substitute['stock_on_hand'] = (float) ($stockMap[$substitute['part_id']] ?? 0);
                            return $substitute;
                        })
                        ->values();

                    $substituteWithStock = $substitutes
                        ->first(fn (array $substitute) => (float) ($substitute['stock_on_hand'] ?? 0) > 0);

                    $distinctSizes = collect([$material['component_size'] ?? ''])
                        ->merge($substitutes->pluck('size'))
                        ->map(fn ($size) => trim((string) $size))
                        ->filter()
                        ->unique()
                        ->values();

                    $sizeDisplay = trim((string) ($material['component_size'] ?? ''));
                    $sizeNote = null;

                    if ($calcMode === 'with_substitute' && $substituteWithStock && $stockOnHand <= 0) {
                        $sizeDisplay = trim((string) ($substituteWithStock['size'] ?? $sizeDisplay));
                        $sizeNote = 'Ikut substitute aktif: ' . ($substituteWithStock['part_no'] ?? '-');
                    } elseif ($substitutes->isNotEmpty() && $distinctSizes->count() > 1) {
                        $sizeDisplay = $sizeDisplay !== '' ? $sizeDisplay : 'Flexible';
                        $sizeNote = 'Flexible, ikut part substitute yang dipakai';
                    }

                    $substituteStock = (float) $substitutes->sum('stock_on_hand');
                    $effectiveStock = $calcMode === 'strict'
                        ? $stockOnHand
                        : $stockOnHand + $substituteStock;
                    $netQty = max(0, round((float) $material['gross_qty'] - $effectiveStock, 4));
                    $isRmBuyItem = $this->isRmBuyMaterial($material);

                    return [
                        'component_part_id' => $material['component_part_id'],
                        'component_part_no' => $material['component_part_no'],
                        'component_part_name' => $material['component_part_name'],
                        'component_size' => $material['component_size'] ?? '',
                        'size_display' => $sizeDisplay,
                        'size_note' => $sizeNote,
                        'component_classification' => $material['component_classification'],
                        'make_or_buy' => $material['make_or_buy'] ?: 'N/A',
                        'gross_qty' => (float) $material['gross_qty'],
                        'stock_on_hand' => $stockOnHand,
                        'substitute_stock' => $substituteStock,
                        'effective_stock_on_hand' => $effectiveStock,
                        'net_qty' => $netQty,
                        'uom' => $material['uom'],
                        'status' => !$isRmBuyItem ? 'N/A' : ($netQty <= 0 ? 'available' : 'shortage'),
                        'substitutes' => $substitutes->all(),
                        'bom_index' => $material['bom_index'],
                        'process_name' => $material['process_name'],
                    ];
                });

            $materials = $this->sortMaterials($materials, $sortBy, $sortDir);

            return [
                'order' => $payload['order'],
                'has_bom' => (bool) ($payload['has_bom'] ?? false),
                'materials' => $materials,
                'material_count' => $materials->count(),
                'shortage_count' => $materials->where('status', 'shortage')->count(),
                'shortage_total' => (float) $materials->where('status', 'shortage')->sum('net_qty'),
            ];
        })->values();

        $totalOrders = $requirementsByOrder->count();
        $totalComponents = (int) $requirementsByOrder->sum('material_count');
        $totalShortage = (int) $requirementsByOrder->sum('shortage_count');
        $totalFgPlanned = $totalOrders;

        return view('production.material-requirement.index', compact(
            'requirementsByOrder',
            'session',
            'planDate',
            'sortBy',
            'sortDir',
            'totalOrders',
            'totalComponents',
            'totalShortage',
            'totalFgPlanned',
            'calcMode',
            'q',
        ));
    }

    protected function sortMaterials(Collection $materials, string $sortBy, string $sortDir): Collection
    {
        return $materials
            ->sortBy(function (array $item) use ($sortBy) {
                return match ($sortBy) {
                    'bom' => $item['bom_index'] ?? 0, // Use explosion order
                    'component' => $item['component_part_no'],
                    'type' => $item['make_or_buy'],
                    'status' => $item['status'] === 'shortage' ? 0 : 1,
                    'net_qty' => -$item['net_qty'],
                    default => $item['component_part_no'],
                };
            }, SORT_REGULAR, $sortDir === 'desc')
            ->values();
    }
}
