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
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'username' => 'admin'
            ]
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

        // 4. Raw Material (GCI Part + Vendor Part)
        // 4a. Create Internal GCI Part for RM
        $rmGciPart = GciPart::firstOrCreate(
            ['part_no' => 'RM-CORE-01'],
            [
                'part_name' => 'Core Material A',
                'classification' => 'RM',
                'status' => 'active',
                'customer_id' => null // Internal RM usually doesn't belong to a customer directly like FG
            ]
        );

        // 4b. Create Vendor Part linked to GCI Part
        $part = Part::firstOrCreate(
            ['part_no' => 'RM-CORE-01'],
            [
                'part_name_gci' => 'Core Material A',
                'vendor_id' => $vendor->id,
                'gci_part_id' => $rmGciPart->id, // Link to GCI Part
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
            ['part_id' => $gciPart->id],
            [
                'revision' => 'A',
                'effective_date' => now(),
                'status' => 'active'
            ]
        );

        BomItem::firstOrCreate(
            ['bom_id' => $bom->id, 'component_part_id' => $rmGciPart->id], // Use GCI Part ID
            [
                'component_part_no' => $rmGciPart->part_no,
                'usage_qty' => 2.0,
                'consumption_uom' => 'PCS'
            ]
        );

        // 7. Customer Demand for Next Month (2026-02)
        // Since we are doing monthly periods, let's use 2026-02
        $targetPeriod = '2026-02';

        // Let's create a Customer PO as a source for Forecast
        \Illuminate\Support\Facades\DB::table('customer_pos')->insertOrIgnore([
            'customer_id' => $customer->id,
            'po_date' => Carbon::now()->format('Y-m-d'),
            'po_no' => 'PO-TEST-001',
            'period' => $targetPeriod,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $poId = \Illuminate\Support\Facades\DB::table('customer_pos')->where('po_no', 'PO-TEST-001')->value('id');

        // Create Planning Import Record
        $importId = \Illuminate\Support\Facades\DB::table('customer_planning_imports')->insertGetId([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'imported_by' => User::first()->id, // Admin user
            'imported_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('customer_planning_rows')->insertOrIgnore([
            'import_id' => $importId,
            'part_id' => $gciPart->id,
            'customer_part_no' => 'CUST-PN-01', // Example customer part no
            'period' => $targetPeriod,
            'qty' => 500,
            'row_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Test data seeded for period: $targetPeriod\n";
    }
}
