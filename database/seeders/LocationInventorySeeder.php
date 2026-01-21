<?php

namespace Database\Seeders;

use App\Models\ArrivalItem;
use App\Models\LocationInventory;
use App\Models\Receive;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationInventorySeeder extends Seeder
{
    /**
     * Seed location_inventory table from existing receives data
     */
    public function run(): void
    {
        $this->command->info('Populating location_inventory from receives...');

        // Get all receives that passed QC, grouped by part and location
        $receives = Receive::with('arrivalItem')
            ->where('qc_status', 'pass')
            ->whereNotNull('location_code')
            ->get();

        $locationStocks = [];

        foreach ($receives as $receive) {
            $partId = $receive->arrivalItem->part_id ?? null;
            $locationCode = $receive->location_code;

            if (!$partId || !$locationCode) {
                continue;
            }

            $key = "{$partId}_{$locationCode}";

            if (!isset($locationStocks[$key])) {
                $locationStocks[$key] = [
                    'part_id' => $partId,
                    'location_code' => $locationCode,
                    'qty_on_hand' => 0,
                ];
            }

            $locationStocks[$key]['qty_on_hand'] += $receive->qty;
        }

        // Insert or update location_inventory records
        $count = 0;
        foreach ($locationStocks as $stock) {
            LocationInventory::updateOrCreate(
                [
                    'part_id' => $stock['part_id'],
                    'location_code' => $stock['location_code'],
                ],
                [
                    'qty_on_hand' => $stock['qty_on_hand'],
                    'last_counted_at' => now(),
                ]
            );
            $count++;
        }

        $this->command->info("✓ Created/updated {$count} location inventory records");
    }
}
