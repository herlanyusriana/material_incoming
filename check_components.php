<?php

// Check customer_part_components mapping
$comps = DB::table('customer_part_components as c')
    ->join('gci_parts as g', 'g.id', '=', 'c.gci_part_id')
    ->select('c.customer_part_id', 'g.id as gci_id', 'g.part_no', 'g.classification', 'g.status')
    ->limit(50)
    ->get();

echo "=== Customer Part Components with GCI Parts ===\n\n";

$grouped = $comps->groupBy('customer_part_id');
foreach ($grouped as $cpId => $items) {
    echo "Customer Part ID: $cpId\n";
    foreach ($items as $item) {
        $fg = $item->classification === 'FG' && $item->status === 'active' ? '✓' : '✗';
        echo "  [$fg] GCI ID {$item->gci_id}: {$item->part_no} [{$item->classification}/{$item->status}]\n";
    }
    echo "\n";
}

// Check specifically for customer part ID 5 (GN-304SHBR.AEPPEIN)
echo "\n=== Check CustomerPart with components (ID from screenshot) ===\n";
$cp = \App\Models\CustomerPart::where('customer_part_no', 'GN-304SHBR.AEPPEIN')->with('components.part')->first();
if ($cp) {
    echo "CustomerPart: {$cp->customer_part_no} (ID: {$cp->id})\n";
    echo "Components:\n";
    foreach ($cp->components as $c) {
        $p = $c->part;
        echo "  - GCI {$c->gci_part_id}: " . ($p ? "{$p->part_no} [{$p->classification}/{$p->status}]" : "NO PART") . "\n";
    }
}
