<?php

use App\Models\GciPart;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciInventory;
use App\Models\FgInventory;
use App\Models\ProductionOrder;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\Outgoing\DeliveryNoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Mock Auth
$user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
Auth::login($user);

echo "--- STARTING E2E TEST ---\n";

DB::transaction(function () {
    // 1. SETUP
    echo "[1] Setup Data...\n";
    $fgPart = GciPart::create(['part_no' => 'TEST-FG-001', 'part_name' => 'Test FG', 'classification' => 'FG', 'uom_id' => 1]);
    $rmPart = GciPart::create(['part_no' => 'TEST-RM-001', 'part_name' => 'Test RM', 'classification' => 'RM', 'uom_id' => 1]);
    
    $bom = Bom::create(['part_id' => $fgPart->id, 'bom_no' => 'BOM-TEST-001']);
    BomItem::create(['bom_id' => $bom->id, 'component_part_id' => $rmPart->id, 'usage_qty' => 2]);
    
    // Initial Stock RM = 100
    GciInventory::create(['gci_part_id' => $rmPart->id, 'on_hand' => 100]);
    echo "    - Created FG: {$fgPart->part_no}, RM: {$rmPart->part_no}, Stock RM: 100\n";
    
    // 2. PRODUCTION
    echo "[2] Production Flow...\n";
    $controller = new ProductionOrderController();
    
    // Create Order
    $req = new Request([
        'gci_part_id' => $fgPart->id,
        'plan_date' => now()->toDateString(),
        'qty_planned' => 10,
        'production_order_number' => 'PO-TEST-001'
    ]);
    
    // Simulate Store (bypassing redirect)
    $order = ProductionOrder::create($req->all() + ['status' => 'planned', 'workflow_stage' => 'created', 'created_by' => Auth::id()]);
    echo "    - Order Created: {$order->production_order_number} (Qty: 10)\n";
    
    // Check Material
    $controller->checkMaterial($order); // Should pass
    $order->refresh(); // getting status update
    echo "    - Order Status after Material Check: {$order->status} (Expected: released)\n";
    
    // Start
    $controller->startProduction($order);
    $order->refresh();
    echo "    - Order Status after Start: {$order->status} (Expected: in_production)\n";
    
    // Finish (this triggers Inventory Update)
    // We need to set qty_actual manually as controller defaults it or expects it? 
    // Logic: $order->qty_actual = $order->qty_planned; in finishProduction
    $controller->finishProduction($order);
    $order->refresh();
    echo "    - Order Status after Finish: {$order->status} (Expected: completed)\n";
    
    // VERIFY INVENTORY 1
    $fgStock = FgInventory::where('gci_part_id', $fgPart->id)->sum('qty_on_hand');
    $rmStock = GciInventory::where('gci_part_id', $rmPart->id)->sum('on_hand');
    
    echo "    [VERIFICATION]\n";
    echo "    - FG Stock: {$fgStock} (Expected: 10)\n";
    echo "    - RM Stock: {$rmStock} (Expected: 100 - 10*2 = 80)\n";
    
    if ($fgStock != 10 || $rmStock != 80) {
        throw new Exception("Inventory Verification Failed!");
    }
    
    // 3. OUTGOING
    echo "[3] Outgoing Flow...\n";
    $dnController = new DeliveryNoteController();
    $customer = \App\Models\Customer::first();
    if (!$customer) {
        $customer = \App\Models\Customer::create([
             'code' => 'CUST-TEST', 'name' => 'Test Customer', 'status' => 'active', 'currency' => 'IDR', 'address' => 'Test'
        ]);
    }
    
    // Create DN
    $dn = DeliveryNote::create([
        'dn_no' => 'DN-TEST-001',
        'customer_id' => $customer->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'draft'
    ]);
    $item = DnItem::create([
        'dn_id' => $dn->id,
        'gci_part_id' => $fgPart->id,
        'qty' => 5
    ]);
    echo "    - Created DN: {$dn->dn_no} (Qty: 5)\n";
    
    // Ship
    $dnController->ship($dn);
    $dn->refresh();
    echo "    - DN Status: {$dn->status} (Expected: shipped)\n";
    
    // VERIFY INVENTORY 2
    $fgStockAfter = FgInventory::where('gci_part_id', $fgPart->id)->sum('qty_on_hand');
    echo "    [VERIFICATION]\n";
    echo "    - FG Stock: {$fgStockAfter} (Expected: 10 - 5 = 5)\n";
    
    if ($fgStockAfter != 5) {
        throw new Exception("Outgoing Verification Failed!");
    }
    
    echo "--- TEST COMPLETED SUCCESSFULLY ---\n";
    
    // Rollback to clean up
    throw new Exception("Rollback for cleanup test data.");
});
