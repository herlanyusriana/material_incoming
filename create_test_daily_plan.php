<?php

use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanRow;
use App\Models\OutgoingDailyPlanCell;

$lg = Customer::where('code', 'LG')->first();
echo "Customer LG ID: " . $lg->id . "\n";

// Get 5 customer parts for LG that have components
$customerParts = CustomerPart::where('customer_id', $lg->id)
    ->whereHas('components', function ($q) {
        $q->whereHas('part', function ($q2) {
            $q2->where('classification', 'FG')->where('status', 'active');
        });
    })
    ->with([
        'components' => function ($q) {
            $q->whereHas('part', function ($q2) {
                $q2->where('classification', 'FG')->where('status', 'active');
            });
            $q->with('part');
        }
    ])
    ->limit(5)
    ->get();

echo "Found " . $customerParts->count() . " customer parts\n\n";

$today = now()->toDateString();
$plan = OutgoingDailyPlan::create([
    'date_from' => $today,
    'date_to' => $today,
    'status' => 'draft',
]);
echo "Created Daily Plan ID: " . $plan->id . "\n\n";

$totalRows = 0;
$rowNo = 1;

foreach ($customerParts as $cp) {
    echo "=== CustomerPart: " . $cp->customer_part_no . " (ID: " . $cp->id . ") ===\n";
    echo "Components count: " . $cp->components->count() . "\n";

    foreach ($cp->components as $comp) {
        $part = $comp->part;
        if (!$part) {
            echo "  [SKIP] Component has no part\n";
            continue;
        }

        echo "  -> Creating row for GCI " . $part->id . ": " . $part->part_no . " (" . $part->part_name . ")\n";

        $row = OutgoingDailyPlanRow::create([
            'plan_id' => $plan->id,
            'row_no' => $rowNo++,
            'production_line' => 'LINE-1',
            'part_no' => $cp->customer_part_no,
            'customer_part_id' => $cp->id,
            'gci_part_id' => $part->id,
        ]);

        OutgoingDailyPlanCell::create([
            'row_id' => $row->id,
            'plan_date' => $today,
            'seq' => 1,
            'qty' => 100,
        ]);

        $totalRows++;
    }
    echo "\n";
}

echo "========================================\n";
echo "TOTAL ROWS CREATED: " . $totalRows . "\n";
echo "Date: " . $today . "\n";
echo "========================================\n";

// Verify
echo "\nVerification - Rows by GCI Part:\n";
$verify = DB::table('outgoing_daily_plan_rows as r')
    ->join('gci_parts as g', 'g.id', '=', 'r.gci_part_id')
    ->select('g.part_no', 'g.part_name', DB::raw('count(*) as cnt'))
    ->groupBy('g.part_no', 'g.part_name')
    ->get();

foreach ($verify as $v) {
    echo "  " . $v->part_no . " (" . $v->part_name . "): " . $v->cnt . " rows\n";
}
