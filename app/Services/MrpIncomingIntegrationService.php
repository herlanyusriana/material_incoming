<?php

namespace App\Services;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use App\Models\NewSchema\Incoming\IncomingReceive;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MrpIncomingIntegrationService
{
    /**
     * Get incoming quantities for parts within a date range
     * This includes both planned arrivals and received materials
     *
     * @param array $partIds Array of GCI Part IDs
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Associative array of [part_id => [date => quantity]]
     */
    public function getIncomingQuantities(array $partIds, string $startDate, string $endDate): array
    {
        $incomingData = [];

        foreach ($partIds as $partId) {
            $incomingData[$partId] = [];
        }

        // Get arrival items directly by gci_part_id and invoice_date range
        $arrivalItems = IncomingArrivalItem::query()
            ->with('arrival:id,invoice_date')
            ->whereIn('gci_part_id', $partIds)
            ->whereHas('arrival', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('invoice_date', [$startDate, $endDate]);
            })
            ->select('id', 'arrival_id', 'gci_part_id', 'qty_goods')
            ->get();

        foreach ($arrivalItems as $item) {
            $gciPartId = (int) $item->gci_part_id;
            if (!isset($incomingData[$gciPartId]) || !$item->arrival?->invoice_date) {
                continue;
            }

            $date = $item->arrival->invoice_date->format('Y-m-d');
            $incomingData[$gciPartId][$date] = ($incomingData[$gciPartId][$date] ?? 0) + (float) $item->qty_goods;
        }

        // Also include received quantities by ata_date range
        $receivedItems = IncomingReceive::query()
            ->with('arrivalItem:id,gci_part_id')
            ->whereHas('arrivalItem', function ($query) use ($partIds) {
                $query->whereIn('gci_part_id', $partIds);
            })
            ->whereBetween('ata_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('id', 'arrival_item_id', 'qty', 'ata_date')
            ->get();

        foreach ($receivedItems as $receive) {
            $gciPartId = (int) ($receive->arrivalItem?->gci_part_id ?? 0);
            if ($gciPartId <= 0 || !isset($incomingData[$gciPartId]) || !$receive->ata_date) {
                continue;
            }

            $date = $receive->ata_date->format('Y-m-d');
            $incomingData[$gciPartId][$date] = ($incomingData[$gciPartId][$date] ?? 0) + (float) $receive->qty;
        }

        return $incomingData;
    }

    /**
     * Get total incoming quantity for a specific part within a date range
     *
     * @param int $gciPartId GCI Part ID
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return float Total incoming quantity
     */
    public function getTotalIncomingForPart(int $gciPartId, string $startDate, string $endDate): float
    {
        $incomingData = $this->getIncomingQuantities([$gciPartId], $startDate, $endDate);
        
        $total = 0;
        if (isset($incomingData[$gciPartId])) {
            foreach ($incomingData[$gciPartId] as $date => $qty) {
                $total += $qty;
            }
        }
        
        return $total;
    }

    /**
     * Get incoming quantities grouped by week for MRP planning
     *
     * @param array $partIds Array of GCI Part IDs
     * @param string $startWeek Start week in Y-m format (e.g., "2024-W01")
     * @param string $endWeek End week in Y-m format
     * @return array Associative array of [part_id => [week => quantity]]
     */
    public function getIncomingQuantitiesByWeek(array $partIds, string $startWeek, string $endWeek): array
    {
        // Convert week format to date range
        $startWeekDate = Carbon::createFromFormat('o-W', $startWeek . '-1')->startOfWeek();
        $endWeekDate = Carbon::createFromFormat('o-W', $endWeek . '-1')->endOfWeek();
        
        $incomingData = $this->getIncomingQuantities($partIds, $startWeekDate->format('Y-m-d'), $endWeekDate->format('Y-m-d'));
        
        // Group by week
        $weeklyData = [];
        foreach ($incomingData as $partId => $dailyData) {
            $weeklyData[$partId] = [];
            foreach ($dailyData as $date => $qty) {
                $week = Carbon::parse($date)->format('o-W');
                if (!isset($weeklyData[$partId][$week])) {
                    $weeklyData[$partId][$week] = 0;
                }
                $weeklyData[$partId][$week] += $qty;
            }
        }
        
        return $weeklyData;
    }

    /**
     * Update MRP calculations with incoming stock information
     *
     * @param array $mrpData Original MRP data
     * @param string $startDate Start date for incoming calculation
     * @param string $endDate End date for incoming calculation
     * @return array Updated MRP data with incoming stock
     */
    public function updateMrpDataWithIncoming(array $mrpData, string $startDate, string $endDate): array
    {
        // Extract part IDs from MRP data
        $partIds = [];
        foreach ($mrpData as $row) {
            $partIds[] = $row['part']->id;
        }

        // Get incoming quantities
        $incomingQuantities = $this->getIncomingQuantities($partIds, $startDate, $endDate);

        // Update MRP data with incoming information
        foreach ($mrpData as &$row) {
            $partId = $row['part']->id;
            
            // Calculate total incoming for this part
            $totalIncoming = 0;
            if (isset($incomingQuantities[$partId])) {
                foreach ($incomingQuantities[$partId] as $date => $qty) {
                    $totalIncoming += $qty;
                }
            }
            
            // Update the row with incoming data
            $row['incoming_total'] = $totalIncoming;
            
            // Recalculate end stock considering incoming
            $row['end_stock'] = $row['initial_stock'] + $totalIncoming - $row['demand_total'];
            
            // Recalculate net required based on updated end stock
            $row['net_required'] = $row['end_stock'] < 0 ? abs($row['end_stock']) : 0;
            
            // Update daily data with incoming information
            foreach ($row['days'] as $date => &$dayData) {
                if (isset($incomingQuantities[$partId][$date])) {
                    $dayData['incoming'] = $incomingQuantities[$partId][$date];
                    
                    // Recalculate projected stock for the day
                    $dayData['projected_stock'] = $dayData['projected_stock'] + $dayData['incoming'];
                    
                    // Recalculate net required for the day
                    $dayData['net_required'] = $dayData['projected_stock'] < 0 ? abs($dayData['projected_stock']) : 0;
                }
            }
        }
        
        return $mrpData;
    }
}