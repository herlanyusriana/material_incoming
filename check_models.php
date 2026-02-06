<?php

use App\Models\GciPart;
use App\Models\CustomerPart;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking GCI Part data for 'model' and 'part_name' vs Customer Part 'customer_part_name'.\n";

$fgParts = GciPart::where('classification', 'FG')->limit(10)->get();

foreach ($fgParts as $p) {
    echo "GCI Part: {$p->part_no} | Model (GCI): '{$p->model}' | Name (GCI): '{$p->part_name}'\n";

    // Check linked customer parts
    $comps = \App\Models\CustomerPartComponent::where('gci_part_id', $p->id)->with('customerPart')->get();
    foreach ($comps as $c) {
        $cp = $c->customerPart;
        if ($cp) {
            echo "   -> Linked Customer Part: {$cp->customer_part_no} | Name: '{$cp->customer_part_name}' | Case: '{$cp->case_name}'\n";
        }
    }
    echo "--------------------------------------------------\n";
}
