<?php

namespace App\Services;

use App\Models\ProductionOrder;
use App\Models\ProductionPlanningLine;
use App\Models\ProductionPlanningSession;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionPlanningBoardService
{
    public function setDailyQuantity(
        int $partId,
        CarbonInterface|string $date,
        float $qty,
        ?int $userId
    ): ProductionPlanningLine {
        $planDate = Carbon::parse($date)->startOfDay();
        $session = ProductionPlanningSession::query()->firstOrCreate(
            ['plan_date' => $planDate->toDateString()],
            [
                'planning_days' => 3,
                'status' => 'draft',
                'created_by' => $userId,
            ]
        );

        $line = ProductionPlanningLine::query()->firstOrNew([
            'session_id' => $session->id,
            'gci_part_id' => $partId,
        ]);

        if (!$line->exists) {
            $line->fill([
                'stock_fg_gci' => 0,
                'delivery_requirement_qty' => 0,
                'delivery_requirement_date_from' => $planDate,
                'delivery_requirement_date_to' => $planDate,
                'shift_1_qty' => 0,
                'shift_2_qty' => 0,
                'shift_3_qty' => 0,
                'sort_order' => (int) ProductionPlanningLine::query()
                    ->where('session_id', $session->id)
                    ->max('sort_order') + 1,
            ]);
        }

        $line->plan_qty = max(0, $qty);
        $line->save();

        return $line->fresh(['session', 'gciPart']);
    }

    public function rows(CarbonInterface|string $startDate, int $days = 3): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $dates = collect(range(0, max(1, $days) - 1))
            ->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString());

        $lines = ProductionPlanningLine::query()
            ->with([
                'session',
                'gciPart.boms.items.machine',
                'gciPart.boms.items.wipPart',
                'productionOrders',
            ])
            ->whereHas('session', fn ($query) => $query->whereBetween('plan_date', [
                $dates->first(),
                $dates->last(),
            ]))
            ->get();

        return $lines
            ->groupBy('gci_part_id')
            ->map(function (Collection $partLines) use ($dates) {
                /** @var ProductionPlanningLine $first */
                $first = $partLines->sortBy(fn (ProductionPlanningLine $line) => [
                    (int) $line->sort_order,
                    (int) $line->id,
                ])->first();

                $linesByDate = $partLines->keyBy(
                    fn (ProductionPlanningLine $line) => $line->session->plan_date->toDateString()
                );
                $part = $first->gciPart;
                $bom = $part?->boms
                    ?->where('status', 'active')
                    ->sortByDesc(fn ($candidate) => $candidate->effective_date?->format('Y-m-d') ?? '')
                    ->first();
                $routingItem = $bom?->items
                    ?->first(fn ($item) => $item->machine || $item->wipPart || $item->wip_part_no)
                    ?? $bom?->items?->first();
                $orders = $partLines
                    ->flatMap(fn (ProductionPlanningLine $line) => $line->productionOrders)
                    ->sortBy(fn (ProductionOrder $order) => [
                        (string) $order->plan_date,
                        (int) $order->id,
                    ])
                    ->values();

                return [
                    'part' => $part,
                    'sort_order' => (int) $first->sort_order,
                    'machine_name' => $routingItem?->machine?->name ?: 'Belum ditentukan',
                    'process_name' => $routingItem?->process_name ?: null,
                    'wip_part_no' => $routingItem?->wipPart?->part_no ?: ($routingItem?->wip_part_no ?: '-'),
                    'wip_part_name' => $routingItem?->wipPart?->part_name ?: ($routingItem?->wip_part_name ?: '-'),
                    'daily_quantities' => $dates->mapWithKeys(fn (string $date) => [
                        $date => (float) ($linesByDate->get($date)?->plan_qty ?? 0),
                    ])->all(),
                    'daily_lines' => $dates->mapWithKeys(fn (string $date) => [
                        $date => $linesByDate->get($date),
                    ])->all(),
                    'available_wo_qty' => (float) $orders
                        ->reject(fn (ProductionOrder $order) => in_array($order->status, ['completed', 'cancelled'], true))
                        ->sum(fn (ProductionOrder $order) => max(0, (float) $order->qty_planned - (float) $order->qty_actual)),
                    'orders' => $orders->map(fn (ProductionOrder $order) => [
                        'id' => $order->id,
                        'number' => $order->production_order_number,
                        'plan_date' => Carbon::parse($order->plan_date)->format('d M Y'),
                        'qty' => (float) $order->qty_planned,
                        'status' => $order->status,
                        'url' => route('production.orders.show', $order),
                    ])->all(),
                ];
            })
            ->sortBy(fn (array $row) => [
                $row['machine_name'] === 'Belum ditentukan' ? 1 : 0,
                $row['machine_name'],
                $row['sort_order'],
                $row['part']?->part_no ?? '',
            ])
            ->values();
    }

    public function movePart(
        int $partId,
        CarbonInterface|string $startDate,
        int $days,
        string $direction
    ): Collection {
        $rows = $this->rows($startDate, $days);
        $partIds = $rows->pluck('part.id')->map(fn ($id) => (int) $id)->values();
        $currentIndex = $partIds->search($partId, true);

        if ($currentIndex === false) {
            return $rows;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $partIds->count()) {
            return $rows;
        }

        $currentPartId = $partIds[$currentIndex];
        $partIds[$currentIndex] = $partIds[$targetIndex];
        $partIds[$targetIndex] = $currentPartId;

        $sessionIds = $this->windowSessionIds($startDate, $days);

        DB::transaction(function () use ($partIds, $sessionIds): void {
            foreach ($partIds as $index => $orderedPartId) {
                ProductionPlanningLine::query()
                    ->whereIn('session_id', $sessionIds)
                    ->where('gci_part_id', $orderedPartId)
                    ->update([
                        'sort_order' => $index + 1,
                        'production_sequence' => $index + 1,
                    ]);
            }
        });

        return $this->rows($startDate, $days);
    }

    public function removePart(
        int $partId,
        CarbonInterface|string $startDate,
        int $days
    ): int {
        $sessionIds = $this->windowSessionIds($startDate, $days);
        $lineIds = ProductionPlanningLine::query()
            ->whereIn('session_id', $sessionIds)
            ->where('gci_part_id', $partId)
            ->pluck('id');

        if ($lineIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($lineIds): int {
            ProductionOrder::query()
                ->whereIn('planning_line_id', $lineIds)
                ->update(['planning_line_id' => null]);

            return ProductionPlanningLine::query()
                ->whereIn('id', $lineIds)
                ->delete();
        });
    }

    private function windowSessionIds(CarbonInterface|string $startDate, int $days): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();

        return ProductionPlanningSession::query()
            ->whereBetween('plan_date', [
                $start->toDateString(),
                $start->copy()->addDays(max(1, $days) - 1)->toDateString(),
            ])
            ->pluck('id');
    }
}
