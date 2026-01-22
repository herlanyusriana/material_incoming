<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Truck;
use App\Models\Driver;
use App\Models\DeliveryPlan;
use App\Models\DeliveryStop;
use App\Models\Customer;
use App\Models\DeliveryNote;
use Carbon\Carbon;

class DeliverySeeder extends Seeder
{
    public function run() {
        // Create Trucks
        $trucks = [
            ['plate_no' => 'B 9012 GHI', 'type' => 'Box Truck', 'capacity' => '5 Ton', 'status' => 'in-use'],
            ['plate_no' => 'B 1234 ABC', 'type' => 'Box Truck', 'capacity' => '5 Ton', 'status' => 'available'],
            ['plate_no' => 'B 5678 DEF', 'type' => 'Flatbed', 'capacity' => '8 Ton', 'status' => 'available'],
        ];

        foreach ($trucks as $t) {
            Truck::firstOrCreate(['plate_no' => $t['plate_no']], $t);
        }

        // Create Drivers
        $drivers = [
            ['name' => 'Slamet Riyadi', 'phone' => '0814-5678-9012', 'license_type' => 'SIM B1', 'status' => 'on-delivery'],
            ['name' => 'Budi Santoso', 'phone' => '0812-3456-7890', 'license_type' => 'SIM B1', 'status' => 'available'],
            ['name' => 'Ahmad Yani', 'phone' => '0813-4567-8901', 'license_type' => 'SIM B2', 'status' => 'available'],
        ];

        foreach ($drivers as $d) {
            Driver::firstOrCreate(['name' => $d['name']], $d);
        }

        // Create Sample Plan (DP001) for Today (or Jan 24 as in mockup)
        // Let's use Today to make it relevant
        $today = Carbon::today();
        
        $truck = Truck::where('plate_no', 'B 9012 GHI')->first();
        $driver = Driver::where('name', 'Slamet Riyadi')->first();

        $plan = DeliveryPlan::create([
            'plan_date' => $today, // or '2024-01-24'
            'sequence' => 1,
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => 'in-progress',
            'estimated_departure' => '08:00',
            'estimated_return' => '12:00',
        ]);

        // Stops
        // 1. Toyota
        $toyota = Customer::firstOrCreate(['name' => 'PT Toyota Manufacturing'], ['code' => 'CUST-TOY-001', 'address' => 'Jl. Industri No. 45, Karawang Barat']);
        $stop1 = DeliveryStop::create([
            'plan_id' => $plan->id,
            'customer_id' => $toyota->id,
            'sequence' => 1,
            'estimated_arrival_time' => '09:00',
            'status' => 'completed',
        ]);
        
        // DO for Toyota
        DeliveryNote::create([
            'dn_no' => 'DO-2024-001',
            'customer_id' => $toyota->id,
            'delivery_date' => $today,
            'delivery_plan_id' => $plan->id,
            'delivery_stop_id' => $stop1->id,
            'status' => 'completed',
        ]);

        // 2. Honda
        $honda = Customer::firstOrCreate(['name' => 'PT Honda Precision Parts'], ['code' => 'CUST-HON-002', 'address' => 'Jl. Raya Bekasi KM 28, Bekasi']);
        $stop2 = DeliveryStop::create([
            'plan_id' => $plan->id,
            'customer_id' => $honda->id,
            'sequence' => 2,
            'estimated_arrival_time' => '10:30',
            'status' => 'in-progress',
        ]);

        DeliveryNote::create([
            'dn_no' => 'DO-2024-002',
            'customer_id' => $honda->id,
            'delivery_date' => $today,
            'delivery_plan_id' => $plan->id,
            'delivery_stop_id' => $stop2->id,
            'status' => 'on-delivery',
        ]);
    }
}
