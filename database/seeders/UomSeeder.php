<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $uoms = [
            // Quantity
            ['code' => 'PCS', 'name' => 'Pieces', 'category' => 'quantity'],
            ['code' => 'SET', 'name' => 'Set', 'category' => 'quantity'],
            ['code' => 'PAIR', 'name' => 'Pair', 'category' => 'quantity'],
            ['code' => 'DOZ', 'name' => 'Dozen', 'category' => 'quantity'],
            ['code' => 'GRS', 'name' => 'Gross', 'category' => 'quantity'],
            ['code' => 'ROLL', 'name' => 'Roll', 'category' => 'quantity'],
            
            // Weight
            ['code' => 'KGM', 'name' => 'Kilogram', 'category' => 'weight'],
            ['code' => 'GRM', 'name' => 'Gram', 'category' => 'weight'],
            ['code' => 'TNE', 'name' => 'Metric Ton', 'category' => 'weight'],
            ['code' => 'LBR', 'name' => 'Pound', 'category' => 'weight'],
            
            // Length
            ['code' => 'MTR', 'name' => 'Meter', 'category' => 'length'],
            ['code' => 'CMT', 'name' => 'Centimeter', 'category' => 'length'],
            ['code' => 'MMT', 'name' => 'Millimeter', 'category' => 'length'],
            ['code' => 'KMT', 'name' => 'Kilometer', 'category' => 'length'],
            ['code' => 'INH', 'name' => 'Inch', 'category' => 'length'],
            ['code' => 'FOT', 'name' => 'Foot', 'category' => 'length'],
            
            // Volume
            ['code' => 'LTR', 'name' => 'Liter', 'category' => 'volume'],
            ['code' => 'MLT', 'name' => 'Milliliter', 'category' => 'volume'],
            ['code' => 'MTQ', 'name' => 'Cubic Meter', 'category' => 'volume'],
            ['code' => 'GAL', 'name' => 'Gallon', 'category' => 'volume'],
            
            // Area
            ['code' => 'MTK', 'name' => 'Square Meter', 'category' => 'area'],
            ['code' => 'CMK', 'name' => 'Square Centimeter', 'category' => 'area'],
            
            // Time
            ['code' => 'HUR', 'name' => 'Hour', 'category' => 'time'],
            ['code' => 'MIN', 'name' => 'Minute', 'category' => 'time'],
            ['code' => 'SEC', 'name' => 'Second', 'category' => 'time'],
        ];

        foreach ($uoms as $uom) {
            Uom::updateOrCreate(
                ['code' => $uom['code']],
                $uom
            );
        }
    }
}
