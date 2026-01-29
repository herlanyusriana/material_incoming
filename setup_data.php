<?php
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\CustomerPo;

try {
    // 1. Customer Part Header
    $cp = CustomerPart::firstOrCreate(
        ['customer_id' => 2, 'customer_part_no' => 'PN-002'],
        [
            'customer_part_name' => 'Part 2',
            'status' => 'ACTIVE'
        ]
    );
    echo "CustomerPart ID: " . $cp->id . PHP_EOL;

    // 2. Component Mapping (Link to Internal Part 2)
    $cpc = CustomerPartComponent::firstOrCreate(
        ['customer_part_id' => $cp->id, 'part_id' => 2],
        ['usage_qty' => 1]
    );
    echo "Component Mapping ID: " . $cpc->id . PHP_EOL;

    // 3. PO (Linked to Internal Part 2)
    // Note: Model says 'part_id' belongs to GciPart. 
    $po = CustomerPo::firstOrCreate(
        ['customer_id' => 2, 'period' => '2026-W02', 'part_id' => 2],
        [
            'po_no' => 'PO-W02-AUTO',
            'qty' => 5000,
            'status' => 'ACTIVE'
        ]
    );
    echo "PO ID: " . $po->id . PHP_EOL;

} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
