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

        echo "Test data seeded successfully.\n";
    }
}
