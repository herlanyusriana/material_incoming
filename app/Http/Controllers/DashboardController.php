<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\DeliveryNote;
use App\Models\PricingMaster;
use App\Models\ProductionOrder;
use App\Models\Receive;
use App\Models\StockOpnameItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ── Incoming Material Data ──
        $departures = Arrival::with(['vendor', 'creator', 'items.receives'])
            ->latest()
            ->paginate(10);

        $incomingSummary = [
            'total_departures' => Arrival::count(),
            'total_receives' => Receive::count(),
            'pending_items' => ArrivalItem::whereHas('arrival')
                ->where('qty_goods', '>', 0)
                ->get()
                ->filter(function ($item) {
                    $totalReceived = $item->receives->sum('qty');
                    return ($item->qty_goods - $totalReceived) > 0;
                })
                ->count(),
            'today_receives' => Receive::whereDate('created_at', now())->count(),
        ];

        $recentReceives = Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->latest()
            ->limit(5)
            ->get();

        $statusCounts = Receive::select('qc_status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('qc_status')
            ->pluck('total', 'qc_status');

        // ── Plant Performance Data ──
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $orders = ProductionOrder::query()
            ->with(['machine', 'part', 'downtimes'])
            ->whereBetween('plan_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->get();

        $plannedQty = (float) $orders->sum(fn($order) => (float) ($order->qty_planned ?? 0));
        $actualQty = (float) $orders->sum(fn($order) => (float) ($order->qty_actual ?? 0));
        $ngQty = (float) $orders->sum(fn($order) => (float) ($order->qty_ng ?? 0));
        $goodQty = max(0, $actualQty - $ngQty);

        $standardMinutes = 0.0;
        $operatingMinutes = 0.0;
        $capacityMinutes = 0.0;
        $machineDayKeys = [];
        $sellingPriceCache = [];

        foreach ($orders as $order) {
            $machine = $order->machine;
            $qtyActual = (float) ($order->qty_actual ?? 0);
            $downtimeMinutes = (float) $order->downtimes->sum(fn($downtime) => (float) ($downtime->duration_minutes ?? 0));

            if ($machine && $qtyActual > 0) {
                $standardMinutes += (($machine->getCycleTimeInSeconds() * $qtyActual) / 60);
            }

            if ($machine) {
                $machineDayKey = ($machine->id ?? 'na') . '|' . Carbon::parse($order->plan_date)->toDateString();
                if (!isset($machineDayKeys[$machineDayKey])) {
                    $machineDayKeys[$machineDayKey] = (float) ($machine->available_hours_per_shift ?? 0) * 60;
                }
            }

            if ($order->start_time && $order->end_time) {
                $runMinutes = max(0, Carbon::parse($order->start_time)->diffInMinutes(Carbon::parse($order->end_time)));
                $operatingMinutes += max(0, $runMinutes - $downtimeMinutes);
            }
        }

        $capacityMinutes = array_sum($machineDayKeys);

        $productionAchievement = $this->safePercent($actualQty, $plannedQty);
        $efficiency = $this->safePercent($standardMinutes, $operatingMinutes);
        $machineLoadHours = round($operatingMinutes / 60, 2);
        $machineCapacityHours = round($capacityMinutes / 60, 2);
        $utilization = $this->safePercent($operatingMinutes, $capacityMinutes);
        $availability = $this->safePercent($operatingMinutes, $capacityMinutes);
        $performance = $this->safePercent($standardMinutes, $operatingMinutes);
        $quality = $this->safePercent($goodQty, $actualQty);
        $oee = round(($availability / 100) * ($performance / 100) * ($quality / 100) * 100, 2);
        $lossTimeMinutes = max(0, round($capacityMinutes - $operatingMinutes, 2));

        $inventoryCounts = StockOpnameItem::query()
            ->whereHas('session', function ($query) use ($from, $to) {
                $query->whereBetween('start_date', [$from, $to]);
            })
            ->get();

        $inventoryDifferenceQty = (float) $inventoryCounts->sum(function ($item) {
            return abs((float) ($item->counted_qty ?? 0) - (float) ($item->system_qty ?? 0));
        });
        $inventoryBaseQty = (float) $inventoryCounts->sum(function ($item) {
            return abs((float) ($item->system_qty ?? 0));
        });
        $inventoryAccuracy = $this->safePercent($inventoryDifferenceQty, $inventoryBaseQty);

        $shortageRmMinutes = round((float) $orders->sum(function ($order) {
            if (!$order->material_requested_at || !$order->material_issued_at) {
                return 0;
            }
            $requestedAt = Carbon::parse($order->material_requested_at);
            $issuedAt = Carbon::parse($order->material_issued_at);
            return max(0, $requestedAt->diffInMinutes($issuedAt, false));
        }), 2);

        $deliveryNotes = DeliveryNote::query()
            ->with(['items.part'])
            ->whereBetween('delivery_date', [$from->toDateString(), $to->toDateString()])
            ->where('status', 'shipped')
            ->get();

        $deliveryShortageMinutes = round((float) $deliveryNotes->sum(function ($deliveryNote) {
            if (!$deliveryNote->shipped_at || !$deliveryNote->delivery_date) {
                return 0;
            }
            $targetAt = Carbon::parse($deliveryNote->delivery_date)->endOfDay();
            $shippedAt = Carbon::parse($deliveryNote->shipped_at);
            return max(0, $targetAt->diffInMinutes($shippedAt, false));
        }), 2);

        $transportDefectCost = 0.0;

        $failureCost = round((float) $orders->sum(function ($order) use (&$sellingPriceCache) {
            $ngQty = (float) ($order->qty_ng ?? 0);
            $gciPartId = (int) ($order->gci_part_id ?? 0);
            if ($ngQty <= 0 || $gciPartId <= 0) {
                return 0;
            }
            $effectiveDate = $order->plan_date ? Carbon::parse($order->plan_date)->toDateString() : now()->toDateString();
            $cacheKey = $gciPartId . '|' . $effectiveDate;
            if (!array_key_exists($cacheKey, $sellingPriceCache)) {
                $sellingPriceCache[$cacheKey] = (float) optional(PricingMaster::resolveCurrentPrice(
                    $gciPartId,
                    'selling_price',
                    [],
                    $effectiveDate
                ))->price;
            }
            return $ngQty * (float) $sellingPriceCache[$cacheKey];
        }), 2);

        $departments = [
            'Production' => [
                ['name' => 'Production Achievement', 'value' => $productionAchievement, 'suffix' => '%', 'formula' => '(Actual / Planned) × 100%'],
                ['name' => 'Efficiency', 'value' => $efficiency, 'suffix' => '%', 'formula' => '(STD Time / Act Time) × 100%'],
                ['name' => 'Machine Load', 'value' => $machineLoadHours, 'suffix' => 'h', 'formula' => 'Actual time machine used'],
                ['name' => 'Machine Capacity', 'value' => $machineCapacityHours, 'suffix' => 'h', 'formula' => 'Available machine hours'],
                ['name' => 'Utilization', 'value' => $utilization, 'suffix' => '%', 'formula' => '(Load / Capacity) × 100%'],
                ['name' => 'OEE', 'value' => $oee, 'suffix' => '%', 'formula' => 'Availability × Performance × Quality'],
                ['name' => 'Loss Time', 'value' => $lossTimeMinutes, 'suffix' => 'min', 'formula' => 'Planned - Operating time'],
            ],
            'Material' => [
                ['name' => 'Inventory Accuracy', 'value' => $inventoryAccuracy, 'suffix' => '%', 'formula' => 'Diff count / Total × 100%'],
                ['name' => 'Shortage RM', 'value' => $shortageRmMinutes, 'suffix' => 'min', 'formula' => 'Late delivery to production'],
            ],
            'Logistics' => [
                ['name' => 'Delivery Shortage', 'value' => $deliveryShortageMinutes, 'suffix' => 'min', 'formula' => 'Late delivery to customers'],
                ['name' => 'Transport Defect Cost', 'value' => $transportDefectCost, 'suffix' => 'IDR', 'formula' => 'NG due to handling'],
            ],
            'Quality' => [
                ['name' => 'Defect Rate', 'value' => $this->safePercent($ngQty, $actualQty), 'suffix' => '%', 'formula' => 'Total NG / Total output × 100%'],
                ['name' => 'Failure Cost', 'value' => $failureCost, 'suffix' => 'IDR', 'formula' => 'Defect qty × sales price'],
            ],
        ];

        $plantSummary = [
            'planned_qty' => round($plannedQty, 2),
            'actual_qty' => round($actualQty, 2),
            'good_qty' => round($goodQty, 2),
            'ng_qty' => round($ngQty, 2),
            'orders_count' => $orders->count(),
            'delivery_notes_count' => $deliveryNotes->count(),
            'stock_opname_lines' => $inventoryCounts->count(),
        ];

        return view('dashboard', compact(
            'departures',
            'incomingSummary',
            'recentReceives',
            'statusCounts',
            'dateFrom',
            'dateTo',
            'departments',
            'plantSummary',
            'availability',
            'performance',
            'quality',
            'oee',
            'productionAchievement',
        ));
    }

    private function safePercent(float $numerator, float $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }
        return round(($numerator / $denominator) * 100, 2);
    }
}
