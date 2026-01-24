<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\GciPart;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use App\Models\Part;
use App\Models\StandardPacking;
use App\Models\Truck;
use App\Models\WarehouseLocation;
use App\Models\LocationInventory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OutgoingDummySeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->startOfDay();
        $dateFrom = $today->copy();
        $dateTo = $today->copy()->addDays(2);

        $userId = User::query()->value('id');

        $custA = Customer::query()->firstOrCreate(
            ['code' => 'CUST-DEMO-A'],
            ['name' => 'Demo Customer A', 'status' => 'active'],
        );
        $custB = Customer::query()->firstOrCreate(
            ['code' => 'CUST-DEMO-B'],
            ['name' => 'Demo Customer B', 'status' => 'active'],
        );

        $gciA = GciPart::query()->firstOrCreate(
            ['part_no' => 'FG-DEMO-001', 'customer_id' => $custA->id],
            ['part_name' => 'Demo FG 001', 'classification' => 'FG', 'status' => 'active'],
        );
        $gciB = GciPart::query()->firstOrCreate(
            ['part_no' => 'FG-DEMO-002', 'customer_id' => $custA->id],
            ['part_name' => 'Demo FG 002', 'classification' => 'FG', 'status' => 'active'],
        );
        $gciC = GciPart::query()->firstOrCreate(
            ['part_no' => 'FG-DEMO-003', 'customer_id' => $custB->id],
            ['part_name' => 'Demo FG 003', 'classification' => 'FG', 'status' => 'active'],
        );

        StandardPacking::query()->updateOrCreate(
            ['gci_part_id' => $gciA->id, 'delivery_class' => 'Main'],
            ['packing_qty' => 10, 'uom' => 'PCS', 'trolley_type' => 'C', 'status' => 'active'],
        );
        StandardPacking::query()->updateOrCreate(
            ['gci_part_id' => $gciB->id, 'delivery_class' => 'Main'],
            ['packing_qty' => 20, 'uom' => 'PCS', 'trolley_type' => 'C', 'status' => 'active'],
        );
        StandardPacking::query()->updateOrCreate(
            ['gci_part_id' => $gciC->id, 'delivery_class' => 'Main'],
            ['packing_qty' => 15, 'uom' => 'PCS', 'trolley_type' => 'C', 'status' => 'active'],
        );

        // Outgoing uses FG gci_parts; kitting/stock per location uses `parts` + `location_inventory`.
        // Create matching `parts.part_no` for demo FG parts so the kitting validation can resolve.
        foreach ([$gciA, $gciB, $gciC] as $gci) {
            $defaults = [
                'part_name_gci' => $gci->part_name,
                'status' => 'active',
                'uom' => 'PCS',
            ];

            if (Schema::hasColumn('parts', 'register_no')) {
                $defaults['register_no'] = $gci->part_no;
            }

            Part::query()->firstOrCreate(
                ['part_no' => $gci->part_no],
                $defaults,
            );
        }

        // Warehouse locations
        $locA = WarehouseLocation::query()->firstOrCreate(
            ['location_code' => 'RACK-A1'],
            ['class' => 'FG', 'zone' => 'A', 'status' => 'ACTIVE'],
        );
        $locB = WarehouseLocation::query()->firstOrCreate(
            ['location_code' => 'RACK-A2'],
            ['class' => 'FG', 'zone' => 'A', 'status' => 'ACTIVE'],
        );

        // Seed batch stock per location (if schema supports it)
        if (Schema::hasTable('location_inventory') && Schema::hasColumn('location_inventory', 'batch_no')) {
            $partsByNo = Part::query()
                ->whereIn('part_no', [$gciA->part_no, $gciB->part_no, $gciC->part_no])
                ->get()
                ->keyBy('part_no');

            $batchRows = [
                ['part_no' => $gciA->part_no, 'loc' => $locA->location_code, 'batch' => 'BATCH-A-001', 'prod' => $today->copy()->subDays(3)->toDateString(), 'qty' => 50],
                ['part_no' => $gciA->part_no, 'loc' => $locA->location_code, 'batch' => 'BATCH-A-002', 'prod' => $today->copy()->subDays(1)->toDateString(), 'qty' => 30],
                ['part_no' => $gciB->part_no, 'loc' => $locA->location_code, 'batch' => 'BATCH-B-001', 'prod' => $today->copy()->subDays(2)->toDateString(), 'qty' => 40],
                ['part_no' => $gciC->part_no, 'loc' => $locB->location_code, 'batch' => 'BATCH-C-001', 'prod' => $today->copy()->subDays(4)->toDateString(), 'qty' => 60],
            ];

            foreach ($batchRows as $row) {
                $part = $partsByNo[$row['part_no']] ?? null;
                if (!$part) {
                    continue;
                }

                LocationInventory::query()->updateOrCreate(
                    [
                        'part_id' => $part->id,
                        'location_code' => $row['loc'],
                        'batch_no' => $row['batch'],
                    ],
                    [
                        'production_date' => $row['prod'],
                        'qty_on_hand' => $row['qty'],
                    ],
                );
            }
        }

        // Truck/Driver (for delivery plan generation)
        Truck::query()->firstOrCreate(
            ['plate_no' => 'B DEMO 001'],
            ['type' => 'Box Truck', 'capacity' => '5 Ton', 'status' => 'available'],
        );
        Driver::query()->firstOrCreate(
            ['name' => 'Demo Driver 1'],
            ['phone' => '0800-000-000', 'license_type' => 'SIM B1', 'status' => 'available'],
        );

        // Create a daily plan & cells (delivery requirements source)
        $plan = OutgoingDailyPlan::query()->create([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'created_by' => $userId,
        ]);

        $rows = [
            ['production_line' => 'LINE-1', 'gci_part' => $gciA, 'seq' => 1, 'qty' => 25],
            ['production_line' => 'LINE-1', 'gci_part' => $gciB, 'seq' => 2, 'qty' => 50],
            ['production_line' => 'LINE-2', 'gci_part' => $gciC, 'seq' => 1, 'qty' => 30],
        ];

        DB::transaction(function () use ($plan, $rows, $today) {
            foreach ($rows as $i => $row) {
                $r = OutgoingDailyPlanRow::query()->create([
                    'plan_id' => $plan->id,
                    'row_no' => $i + 1,
                    'production_line' => $row['production_line'],
                    'part_no' => $row['gci_part']->part_no,
                    'gci_part_id' => $row['gci_part']->id,
                ]);

                OutgoingDailyPlanCell::query()->updateOrCreate(
                    ['row_id' => $r->id, 'plan_date' => $today->toDateString()],
                    ['seq' => $row['seq'], 'qty' => $row['qty']],
                );
            }
        });

        $this->command?->info("Outgoing dummy data created: Plan #{$plan->id} ({$dateFrom->toDateString()}..{$dateTo->toDateString()})");
        $this->command?->info('Try: Outgoing → Delivery Requirements (date range includes today)');
    }
}
