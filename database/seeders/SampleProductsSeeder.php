<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class SampleProductsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'GCI-SHEET-GI', 'name' => 'SHEET, STEEL (GI)', 'uom' => 'SHEET', 'status' => 'active'],
            ['code' => 'GCI-COIL-GI', 'name' => 'COIL, STEEL (GI)', 'uom' => 'KGM', 'status' => 'active'],
            ['code' => 'GCI-PIN-HINGE', 'name' => 'PIN HINGE CENTER', 'uom' => 'PCS', 'status' => 'active'],
            ['code' => 'GCI-TRAY-DRAIN', 'name' => 'TRAY DRAIN VT 12', 'uom' => 'PCS', 'status' => 'active'],
            ['code' => 'GCI-COMP-BASE', 'name' => 'COMP BASE VT 18', 'uom' => 'PCS', 'status' => 'active'],
            ['code' => 'GCI-REINF-OMEGA', 'name' => 'REINFORCE OMEGA', 'uom' => 'KGM', 'status' => 'active'],
            ['code' => 'GCI-MISC-ALPHA', 'name' => 'SUPPORTER HANDLE ALPHA R', 'uom' => 'PCS', 'status' => 'active'],
            ['code' => 'GCI-OBSOLETE', 'name' => 'OBSOLETE SAMPLE', 'uom' => 'PCS', 'status' => 'inactive'],
        ];

        foreach ($rows as $row) {
            Product::updateOrCreate(['code' => strtoupper($row['code'])], $row);
        }
    }
}

