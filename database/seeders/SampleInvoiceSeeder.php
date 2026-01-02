<?php

namespace Database\Seeders;

use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\Part;
use App\Models\Trucking;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'invoice-sample@app.test'],
            [
                'name' => 'Invoice Sample',
                'password' => bcrypt('password'),
            ]
        );

        $vendor = Vendor::firstOrCreate(
            ['vendor_name' => 'Steel Vendor Ltd.'],
            [
                'address' => '99 Harbor Road, Ho Chi Minh, Vietnam',
                'bank_account' => 'ACME BANK 123-456-789',
                'contact_person' => 'Mr. Vendor',
                'email' => 'sales@vendor.test',
                'phone' => '+84 28 0000 0000',
                'status' => 'active',
            ]
        );

        $trucking = Trucking::firstOrCreate(
            ['company_name' => 'Speed Logistics'],
            [
                'address' => 'Jl. Raya Logistik No. 88, Jakarta',
                'phone' => '021-1234567',
                'email' => 'ops@speedlogistics.test',
                'contact_person' => 'Niko',
                'status' => 'active',
            ]
        );

        $partCoil = Part::firstOrCreate(
            ['part_no' => 'STEEL-COIL-025', 'vendor_id' => $vendor->id],
            [
                'register_no' => 'REG-COIL-025',
                'part_name_vendor' => 'STEEL COIL 0,25x100xC',
                'part_name_gci' => 'Steel Coil',
                'hs_code' => '7208.90.00',
                'status' => 'active',
            ]
        );

        $partSheet = Part::firstOrCreate(
            ['part_no' => 'STEEL-SHEET-025', 'vendor_id' => $vendor->id],
            [
                'register_no' => 'REG-SHEET-025',
                'part_name_vendor' => 'STEEL SHEET 0,25x100x417',
                'part_name_gci' => 'Steel Sheet',
                'hs_code' => '7208.90.00',
                'status' => 'active',
            ]
        );

        $arrival = Arrival::firstOrCreate(
            ['invoice_no' => 'INV-SAMPLE-001'],
            [
                'invoice_date' => Carbon::now()->subDays(2),
                'vendor_id' => $vendor->id,
                'trucking_company_id' => $trucking->id,
                'vessel' => 'MV Sample Seas',
                'ETD' => Carbon::now()->addDays(5),
                'bill_of_lading' => 'BL-SAMPLE-001',
                'hs_code' => '7208.90.00',
                'port_of_loading' => 'Ho Chi Minh',
                'country' => 'VIETNAM',
                'container_numbers' => "MSKU1234567\nMSKU1234568",
                'currency' => 'USD',
                'notes' => 'Sample shipment for invoice preview.',
                'created_by' => $user->id,
            ]
        );

        $arrival->containers()->updateOrCreate(
            ['container_no' => 'MSKU1234567'],
            ['seal_code' => null]
        );

        $arrival->containers()->updateOrCreate(
            ['container_no' => 'MSKU1234568'],
            ['seal_code' => null]
        );

        ArrivalItem::updateOrCreate(
            ['arrival_id' => $arrival->id, 'part_id' => $partCoil->id],
            [
                'size' => '0,25x100xC',
                'qty_bundle' => 2,
                'unit_bundle' => 'Coil',
                'qty_goods' => 2,
                'weight_nett' => 2500,
                'unit_weight' => 'KGM',
                'weight_gross' => 2550,
                'price' => 850.75,
                'total_price' => 1701.50,
                'notes' => 'Coil unit',
            ]
        );

        ArrivalItem::updateOrCreate(
            ['arrival_id' => $arrival->id, 'part_id' => $partSheet->id],
            [
                'size' => '0,25x100x417',
                'qty_bundle' => 1,
                'unit_bundle' => 'Sheet',
                'qty_goods' => 417,
                'weight_nett' => 3100,
                'unit_weight' => 'KGM',
                'weight_gross' => 3150,
                'price' => 2.35,
                'total_price' => 979.95,
                'notes' => 'Sheet unit',
            ]
        );
    }
}
