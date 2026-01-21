<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Part;
use App\Models\GciPart;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Customer;
use App\Models\CustomerPo;
use Carbon\Carbon;

class PlanningTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        // 2. Vendor
        $vendor = Vendor::firstOrCreate(
            ['vendor_code' => 'V001'],
            ['vendor_name' => 'Main Supplier', 'vendor_type' => 'local', 'status' => 'active']
        );

        // 3. Customer
        $customer = Customer::firstOrCreate(
            ['code' => 'C001'],
            ['name' => 'Top Customer', 'status' => 'active']
        );

        // 4. Raw Material Part
        $part = Part::firstOrCreate(
            ['part_no' => 'RM-CORE-01'],
            [
                'part_name_gci' => 'Core Material A',
                'vendor_id' => $vendor->id,
                'status' => 'active',
                'uom' => 'PCS',
                'price' => 10.50
            ]
        );

        // 5. Finished Good (GCI Part)
        $gciPart = GciPart::firstOrCreate(
            ['part_no' => 'FG-PLAN-01'],
            [
                'part_name' => 'Finished Product X',
                'classification' => 'FG',
                'status' => 'active',
                'customer_id' => $customer->id
            ]
        );

        // 6. BOM (FG uses 2 RM)
        $bom = Bom::firstOrCreate(
            ['part_id' => $gciPart->id, 'bom_no' => 'BOM-01']
        );

        BomItem::firstOrCreate(
            ['bom_id' => $bom->id, 'component_part_id' => $part->id],
            ['usage_qty' => 2.0]
        );

        // 7. Customer Demand for Next Month (2026-02)
        // Since we are doing monthly periods, let's use 2026-02
        $targetPeriod = '2026-02';

        // Let's create a Customer PO as a source for Forecast
        \Illuminate\Support\Facades\DB::table('customer_pos')->insertOrIgnore([
            'customer_id' => $customer->id,
            'po_date' => Carbon::now()->format('Y-m-d'),
            'po_no' => 'PO-TEST-001',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $poId = \Illuminate\Support\Facades\DB::table('customer_pos')->where('po_no', 'PO-TEST-001')->value('id');

        \Illuminate\Support\Facades\DB::table('customer_planning_rows')->insertOrIgnore([
            'customer_id' => $customer->id,
            'part_no' => $gciPart->part_no,
            'period' => $targetPeriod,
            'qty' => 500,
            'source' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Test data seeded for period: $targetPeriod\n";
    }
}
