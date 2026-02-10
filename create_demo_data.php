<?php

use App\Models\GciPart;
use App\Models\Bom;
use App\Models\GciInventory;
use App\Models\ProductionOrder;

$fg = GciPart::updateOrCreate(
    ['part_no' => 'TEST-FG-01'],
    ['part_name' => 'Demo Finished Good', 'classification' => 'FG', 'uom' => 'PCS', 'model' => 'DEMO-MODEL']
);

$rm1 = GciPart::where('part_no', 'PN-001')->first();
$rm2 = GciPart::where('part_no', 'PN-002')->first();

if (!$rm1 || !$rm2) {
    echo "Required components PN-001 or PN-002 not found.\n";
    exit(1);
}

$bom = Bom::updateOrCreate(
    ['part_id' => $fg->id],
    ['bom_number' => 'BOM-DEMO-01', 'status' => 'active']
);

$bom->items()->updateOrCreate(
    ['component_part_id' => $rm1->id],
    ['usage_qty' => 1, 'make_or_buy' => 'BUY']
);

$bom->items()->updateOrCreate(
    ['component_part_id' => $rm2->id],
    ['usage_qty' => 2, 'make_or_buy' => 'BUY']
);

GciInventory::updateOrCreate(['gci_part_id' => $rm1->id], ['on_hand' => 1000]);
GciInventory::updateOrCreate(['gci_part_id' => $rm2->id], ['on_hand' => 1000]);

ProductionOrder::updateOrCreate(
    ['production_order_number' => 'PO-' . date('Ymd') . '-001'],
    [
        'gci_part_id' => $fg->id,
        'plan_date' => date('Y-m-d'),
        'qty_planned' => 10,
        'status' => 'released',
        'workflow_stage' => 'ready',
        'process_name' => 'Assembly',
        'machine_name' => 'MCH-01'
    ]
);

echo "Demo data created successfully!\n";
