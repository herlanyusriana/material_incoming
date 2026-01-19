<?php

use App\Models\GciPart;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciInventory;
use App\Models\Mps;
use App\Models\MrpRun;
use App\Models\MrpPurchasePlan;
use App\Models\MrpProductionPlan;
use App\Http\Controllers\Planning\MrpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Mock Auth
$user = \App\Models\User::first();
if(!$user) {
    echo "No user found. Create one first.\n";
    exit(1);
}
Auth::login($user);

echo "--- STARTING MRP FLOW TEST ---\n";

DB::transaction(function () use ($user) {
    // 1. SETUP DATA
    $suffix = rand(1000, 9999);
    $fgCode = "FG-MRP-{$suffix}";
    $rmCode = "RM-MRP-{$suffix}";
    $week = "2025-W10";
    
    echo "[1] Creating Parts and BOM...\n";
    // Create Parts
    $fgPart = GciPart::create(['part_no' => $fgCode, 'part_name' => 'FGMock', 'classification' => 'FG', 'uom_id' => 1, 'status' => 'active']);
    $rmPart = GciPart::create(['part_no' => $rmCode, 'part_name' => 'RMMock', 'classification' => 'RM', 'uom_id' => 1, 'status' => 'active']);
    
    // CRM Inventory (Stock)
    GciInventory::create(['gci_part_id' => $rmPart->id, 'on_hand' => 50, 'on_order' => 0]);
    echo "    - FG: {$fgCode}, RM: {$rmCode}\n";
    echo "    - RM Stock: 50\n";
    
    // Create BOM: 1 FG = 2 RM
    $bom = Bom::create(['part_id' => $fgPart->id, 'bom_no' => "BOM-{$suffix}"]);
    BomItem::create(['bom_id' => $bom->id, 'component_part_id' => $rmPart->id, 'usage_qty' => 2]);
    echo "    - BOM Created: 1 FG uses 2 RM\n";
    
    // 2. MPS CREATION
    echo "[2] Creating & Approving MPS...\n";
    $mpsQty = 100;
    $mps = Mps::create([
        'part_id' => $fgPart->id,
        'minggu' => $week,
        'forecast_qty' => $mpsQty,
        'planned_qty' => $mpsQty,
        'status' => 'approved', // Directly approved for test
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);
    echo "    - MPS Approved for {$week}: Qty {$mpsQty}\n";
    
    // 3. GENERATE MRP
    echo "[3] Generating MRP...\n";
    $controller = new MrpController();
    $request = new Request([
        'minggu' => $week,
        'include_saturday' => 0 // 5 Days
    ]);
    $request->setUserResolver(fn () => $user);
    
    // We can't easily call the controller method via route dispatch in tinker script without full app boot, 
    // but we can call the method if we instantiated it and mocked inputs.
    // However, the controller returns a RedirectResponse. We just want to check side effects.
    try {
        $controller->generate($request);
    } catch (\Exception $e) {
        // Redirect might throw exception in CLI or just return object.
        // We ignore if it's just a redirect exception or similar, but verify DB.
        if (!str_contains(get_class($e), 'ValidationException')) {
             // Continue if not validation error
        } else {
             echo "Validation Error: " . $e->getMessage() . "\n";
             throw $e;
        }
    }
    
    echo "    - MRP Generation Logic Executed.\n";
    
    // 4. VERIFICATION
    echo "[4] Verifying Results...\n";
    
    $run = MrpRun::where('minggu', $week)->latest()->first();
    if (!$run) throw new Exception("MRP Run not found!");
    
    // Verify Production Plan (FG)
    // 100 / 5 days = 20 per day
    $prodPlans = MrpProductionPlan::where('mrp_run_id', $run->id)
                ->where('part_id', $fgPart->id)
                ->get();
    
    $totalPlanned = $prodPlans->sum('planned_qty');
    echo "    - FG Production Plan Total: {$totalPlanned} (Expected: 100)\n";
    
    if (abs($totalPlanned - 100) > 0.01) throw new Exception("FG Plan Mismatch");
    
    // Verify Purchase Plan (RM)
    // Demand = 100 * 2 = 200
    // Stock = 50
    // Net = 150
    // Daily = 150 / 5 = 30
    
    $purchPlans = MrpPurchasePlan::where('mrp_run_id', $run->id)
                ->where('part_id', $rmPart->id)
                ->get();
                
    $totalReq = $prodPlans->count() * 2 * 20; // Just verifying logic
    $totalNet = $purchPlans->sum('net_required');
    
    echo "    - RM Purchase Plan Net Total: {$totalNet} (Expected: 150)\n";
    
    if (abs($totalNet - 150) > 0.01) throw new Exception("RM Net Plan Mismatch");
    
    echo "--- MRP FLOW TEST SUCCESS ---\n";
    
    // Rollback
    throw new Exception("Rollback cleanup");
});
