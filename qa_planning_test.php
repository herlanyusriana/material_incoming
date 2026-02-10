<?php

echo "========================================\n";
echo "PLANNING MODULE QA TEST\n";
echo "========================================\n\n";

// 1. Check Customers
echo "1. CUSTOMERS\n";
$customers = \App\Models\Customer::all();
echo "   Total: " . $customers->count() . "\n";
echo "   Active: " . $customers->where('status', 'active')->count() . "\n";
echo "   With code: " . $customers->whereNotNull('code')->count() . "\n\n";

// 2. Check GCI Parts
echo "2. GCI PARTS\n";
$gciParts = \App\Models\GciPart::all();
echo "   Total: " . $gciParts->count() . "\n";
echo "   By classification:\n";
echo "     - FG: " . $gciParts->where('classification', 'FG')->count() . "\n";
echo "     - RM: " . $gciParts->where('classification', 'RM')->count() . "\n";
echo "     - WIP: " . $gciParts->where('classification', 'WIP')->count() . "\n";
echo "     - Other: " . $gciParts->whereNotIn('classification', ['FG', 'RM', 'WIP'])->count() . "\n";
echo "   Active: " . $gciParts->where('status', 'active')->count() . "\n";
echo "   Parts without part_no: " . $gciParts->filter(fn($p) => empty($p->part_no))->count() . " ⚠️\n\n";

// 3. Check Customer Parts
echo "3. CUSTOMER PARTS (Product Mapping)\n";
$customerParts = \App\Models\CustomerPart::with('components')->get();
echo "   Total: " . $customerParts->count() . "\n";
echo "   With components: " . $customerParts->filter(fn($cp) => $cp->components->count() > 0)->count() . "\n";
echo "   Without components: " . $customerParts->filter(fn($cp) => $cp->components->count() === 0)->count() . " ⚠️\n";
$avgComponents = $customerParts->count() > 0
    ? $customerParts->sum(fn($cp) => $cp->components->count()) / $customerParts->count()
    : 0;
echo "   Avg components per part: " . round($avgComponents, 2) . "\n\n";

// 4. Check Customer Part Components
echo "4. CUSTOMER PART COMPONENTS\n";
$components = \App\Models\CustomerPartComponent::with('part')->get();
echo "   Total: " . $components->count() . "\n";
$orphaned = $components->filter(fn($c) => !$c->part);
echo "   Orphaned (no GCI Part): " . $orphaned->count() . " ⚠️\n";
if ($orphaned->count() > 0) {
    echo "   Orphaned IDs: " . $orphaned->pluck('id')->take(5)->implode(', ') . "...\n";
}
echo "\n";

// 5. Check BOMs
echo "5. BILL OF MATERIALS (BOM)\n";
$boms = \App\Models\BomItem::with(['bom.part', 'componentPart'])->get();
echo "   Total BOM items: " . $boms->count() . "\n";
$orphanedBom = $boms->filter(fn($b) => !$b->bom || !$b->componentPart);
echo "   Orphaned entries: " . $orphanedBom->count() . " ⚠️\n";
$uniqueParents = $boms->pluck('bom_id')->unique()->count();
echo "   Unique BOMs: " . $uniqueParents . "\n\n";

// 6. Check Daily Plans
echo "6. DAILY PLANS\n";
$plans = \App\Models\OutgoingDailyPlan::all();
echo "   Total plans: " . $plans->count() . "\n";
$rows = \App\Models\OutgoingDailyPlanRow::all();
echo "   Total rows: " . $rows->count() . "\n";
$rowsNoGci = $rows->whereNull('gci_part_id');
echo "   Rows without GCI Part: " . $rowsNoGci->count() . " ⚠️\n";
$cells = \App\Models\OutgoingDailyPlanCell::all();
echo "   Total cells: " . $cells->count() . "\n\n";

// 7. Data Integrity Issues
echo "========================================\n";
echo "DATA INTEGRITY CHECKS\n";
echo "========================================\n\n";

$issues = [];

// Check: GCI Parts should have unique part_no
$dupParts = DB::table('gci_parts')
    ->select('part_no', DB::raw('count(*) as cnt'))
    ->whereNotNull('part_no')
    ->groupBy('part_no')
    ->havingRaw('count(*) > 1')
    ->get();
if ($dupParts->count() > 0) {
    $issues[] = "Duplicate GCI Part numbers: " . $dupParts->count();
    foreach ($dupParts->take(3) as $d) {
        echo "   - " . $d->part_no . " appears " . $d->cnt . " times\n";
    }
}

// Check: CustomerPartComponents pointing to non-existent GCI Parts
$invalidComps = DB::table('customer_part_components as c')
    ->leftJoin('gci_parts as g', 'g.id', '=', 'c.gci_part_id')
    ->whereNull('g.id')
    ->count();
if ($invalidComps > 0) {
    $issues[] = "CustomerPartComponents with invalid gci_part_id: " . $invalidComps;
}

// Check: BOM items with invalid component
$invalidBomItems = DB::table('bom_items as b')
    ->leftJoin('gci_parts as p', 'p.id', '=', 'b.component_part_id')
    ->whereNull('p.id')
    ->count();
if ($invalidBomItems > 0) {
    $issues[] = "BOM items with invalid component_part_id: " . $invalidBomItems;
}

if (empty($issues)) {
    echo "✅ No critical data integrity issues found!\n\n";
} else {
    echo "⚠️ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "   - " . $issue . "\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "QA TEST COMPLETE\n";
echo "========================================\n";
