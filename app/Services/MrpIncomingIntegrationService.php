<?php

namespace App\Services;

use App\Models\GciPart;
use App\Models\Part;
use App\Models\ArrivalItem;
use App\Models\Receive;
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

        // Initialize the array
        foreach ($partIds as $partId) {
            $incomingData[$partId] = [];
        }

        // Get incoming parts that correspond to the GCI parts
        $gciParts = GciPart::whereIn('id', $partIds)->get();
        
        // Map GCI part numbers to their IDs
        $gciPartNos = [];
        foreach ($gciParts as $part) {
            $gciPartNos[strtoupper(trim($part->part_no))] = $part->id;
        }

        // Get arrival items for parts that match our GCI parts
        $arrivalItems = ArrivalItem::with(['arrival', 'part'])
            ->join('parts', 'arrival_items.part_id', '=', 'parts.id')
            ->join('arrivals', 'arrival_items.arrival_id', '=', 'arrivals.id')
            ->whereIn(DB::raw('UPPER(TRIM(parts.part_no))'), array_keys($gciPartNos))
            ->whereBetween('arrivals.invoice_date', [$startDate, $endDate])
            ->select('arrival_items.*', 'parts.part_no', 'arrivals.invoice_date')
            ->get();

        // Process arrival items
        foreach ($arrivalItems as $item) {
            $partNo = strtoupper(trim($item->part_no));
            $gciPartId = $gciPartNos[$partNo] ?? null;
            
            if ($gciPartId && isset($incomingData[$gciPartId])) {
                $date = $item->arrival->invoice_date->format('Y-m-d');
                
                if (!isset($incomingData[$gciPartId][$date])) {
                    $incomingData[$gciPartId][$date] = 0;
                }
                
                $incomingData[$gciPartId][$date] += $item->qty_goods;
            }
        }

        // Also include received quantities that might not be tied to a specific arrival
        $receivedItems = Receive::with(['arrivalItem.part'])
            ->join('arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->join('parts', 'arrival_items.part_id', '=', 'parts.id')
            ->whereIn(DB::raw('UPPER(TRIM(parts.part_no))'), array_keys($gciPartNos))
            ->whereBetween('receives.created_at', [$startDate, $endDate])
            ->select('receives.*', 'parts.part_no', 'arrival_items.qty_goods')
            ->get();

        foreach ($receivedItems as $receive) {
            $partNo = strtoupper(trim($receive->arrivalItem->part->part_no));
            $gciPartId = $gciPartNos[$partNo] ?? null;
            
            if ($gciPartId && isset($incomingData[$gciPartId])) {
                $date = $receive->created_at->format('Y-m-d');
                
                if (!isset($incomingData[$gciPartId][$date])) {
                    $incomingData[$gciPartId][$date] = 0;
                }
                
                // Add the received quantity
                $incomingData[$gciPartId][$date] += $receive->qty_received ?? 0;
            }
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