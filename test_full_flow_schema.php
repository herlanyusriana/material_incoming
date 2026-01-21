<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Part;
use App\Models\GciPart;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\Receive;
use App\Models\Inventory;
use App\Models\GciInventory;
use App\Models\FgInventory;
use App\Models\ProductionOrder;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\Customer;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\Outgoing\DeliveryNoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Force SQLite Memory & Array Drivers
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);
config(['session.driver' => 'array']);
config(['cache.driver' => 'array']);

echo "--- [STEP 0] SETTING UP SCHEMA ---\n";

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamps();
});

Schema::create('vendors', function (Blueprint $table) {
    $table->id();
    $table->string('vendor_name');
    $table->string('vendor_code')->nullable();
    $table->string('vendor_type')->nullable();
    $table->string('status')->nullable();
    $table->string('currency')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('parts', function (Blueprint $table) {
    $table->id();
    $table->string('part_no')->unique();
    $table->string('part_name_gci')->nullable();
    $table->foreignId('vendor_id')->nullable();
    $table->string('status')->nullable();
    $table->string('uom')->nullable();
    $table->decimal('price', 20, 3)->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('gci_parts', function (Blueprint $table) {
    $table->id();
    $table->string('part_no')->unique();
    $table->string('part_name')->nullable();
    $table->string('classification')->nullable();
    $table->string('status')->nullable();
    $table->string('barcode')->nullable();
    $table->string('model')->nullable();
    $table->foreignId('customer_id')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('boms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('part_id');
    $table->string('bom_no')->nullable();
    $table->timestamps();
});

Schema::create('bom_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bom_id');
    $table->foreignId('component_part_id');
    $table->decimal('usage_qty', 18, 4);
    $table->timestamps();
});

Schema::create('arrivals', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_no')->unique();
    $table->string('arrival_no')->nullable();
    $table->date('invoice_date');
    $table->foreignId('vendor_id');
    $table->string('status')->nullable();
    $table->string('currency')->nullable();
    $table->foreignId('created_by')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('arrival_containers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('arrival_id');
    $table->string('container_no')->nullable();
    $table->string('seal_no')->nullable();
    $table->string('status')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('arrival_container_inspections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('arrival_id');
    $table->string('status')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('arrival_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('arrival_id');
    $table->foreignId('part_id');
    $table->integer('qty_goods');
    $table->string('unit_goods')->nullable();
    $table->decimal('weight_nett', 15, 2)->nullable();
    $table->decimal('weight_gross', 15, 2)->nullable();
    $table->decimal('price', 20, 3)->nullable();
    $table->decimal('total_price', 20, 2)->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('receives', function (Blueprint $table) {
    $table->id();
    $table->foreignId('arrival_item_id');
    $table->string('tag')->nullable();
    $table->integer('qty');
    $table->string('qty_unit')->nullable();
    $table->string('qc_status')->nullable();
    $table->string('location_code')->nullable();
    $table->string('bundle_unit')->nullable();
    $table->integer('bundle_qty')->nullable();
    $table->decimal('weight', 15, 2)->nullable();
    $table->decimal('net_weight', 15, 2)->nullable();
    $table->decimal('gross_weight', 15, 2)->nullable();
    $table->date('ata_date')->nullable();
    $table->string('truck_no')->nullable();
    $table->string('invoice_no')->nullable();
    $table->string('delivery_note_no')->nullable();
    $table->string('jo_po_number')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('inventories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('part_id')->unique();
    $table->decimal('on_hand', 20, 4)->default(0);
    $table->decimal('on_order', 20, 4)->default(0);
    $table->date('as_of_date')->nullable();
    $table->timestamps();
});

Schema::create('gci_inventories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gci_part_id')->unique();
    $table->decimal('on_hand', 20, 4)->default(0);
    $table->decimal('on_order', 20, 4)->default(0);
    $table->date('as_of_date')->nullable();
    $table->timestamps();
});

Schema::create('fg_inventory', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gci_part_id')->unique();
    $table->decimal('qty_on_hand', 20, 4)->default(0);
    $table->string('location')->nullable();
    $table->timestamps();
});

Schema::create('production_orders', function (Blueprint $table) {
    $table->id();
    $table->string('production_order_number')->unique();
    $table->foreignId('gci_part_id');
    $table->date('plan_date');
    $table->decimal('qty_planned', 18, 4);
    $table->decimal('qty_actual', 18, 4)->default(0);
    $table->string('status');
    $table->string('workflow_stage')->nullable();
    $table->dateTime('start_time')->nullable();
    $table->dateTime('end_time')->nullable();
    $table->foreignId('created_by')->nullable();
    $table->timestamps();
});

Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code')->nullable();
    $table->string('status')->nullable();
    $table->string('currency')->nullable();
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('delivery_notes', function (Blueprint $table) {
    $table->id();
    $table->string('dn_no')->unique();
    $table->foreignId('customer_id');
    $table->date('delivery_date');
    $table->string('status');
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('dn_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('dn_id');
    $table->foreignId('gci_part_id');
    $table->decimal('qty', 18, 4);
    $table->softDeletes();
    $table->timestamps();
});

Schema::create('production_inspections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('production_order_id');
    $table->string('type');
    $table->string('status')->nullable();
    $table->timestamps();
});

echo "--- [STEP 1] MASTER DATA SETUP ---\n";

$user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'secret']);
Auth::login($user);

$vendor = Vendor::create(['vendor_name' => 'TEST VENDOR', 'vendor_code' => 'V-TEST', 'vendor_type' => 'import', 'status' => 'active', 'currency' => 'USD']);
$rmPart = Part::create(['part_no' => 'RM-001', 'part_name_gci' => 'Test RM', 'vendor_id' => $vendor->id, 'status' => 'active', 'uom' => 'PCS']);
$gciRmPart = GciPart::create(['part_no' => 'RM-001', 'part_name' => 'Test RM', 'classification' => 'RM', 'status' => 'active']);
$gciFgPart = GciPart::create(['part_no' => 'FG-001', 'part_name' => 'Test FG', 'classification' => 'FG', 'status' => 'active']);

$bom = Bom::create(['part_id' => $gciFgPart->id, 'bom_no' => 'BOM-001']);
BomItem::create(['bom_id' => $bom->id, 'component_part_id' => $gciRmPart->id, 'usage_qty' => 5]);

echo "--- [STEP 2] INCOMING MATERIAL FLOW ---\n";

$arrival = Arrival::create(['invoice_no' => 'INV-TEST-001', 'invoice_date' => now(), 'vendor_id' => $vendor->id, 'status' => 'pending', 'currency' => 'USD', 'created_by' => $user->id]);
$arrivalItem = ArrivalItem::create(['arrival_id' => $arrival->id, 'part_id' => $rmPart->id, 'qty_goods' => 100, 'unit_goods' => 'PCS', 'weight_nett' => 10, 'weight_gross' => 11, 'price' => 1.0, 'total_price' => 100]);

$receiveController = new ReceiveController();
$req = new Request(['receive_date' => now()->toDateString(), 'tags' => [['tag' => 'TAG-001', 'qty' => 100, 'qty_unit' => 'PCS', 'bundle_unit' => 'BOX', 'bundle_qty' => 1, 'qc_status' => 'pass']]]);
try {
    $receiveController->store($req, $arrivalItem);
} catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
    // Redirection caught
}

$rmStockLogistics = Inventory::where('part_id', $rmPart->id)->value('on_hand');
echo "    - Logistics RM Stock: $rmStockLogistics\n";

echo "--- [STEP 3] PRODUCTION FLOW ---\n";

// Manual bridge: update GciInventory based on logistics receive
GciInventory::create(['gci_part_id' => $gciRmPart->id, 'on_hand' => $rmStockLogistics, 'as_of_date' => now()]);

$po = ProductionOrder::create(['production_order_number' => 'PO-001', 'gci_part_id' => $gciFgPart->id, 'plan_date' => now(), 'qty_planned' => 10, 'status' => 'planned', 'workflow_stage' => 'created', 'created_by' => $user->id]);

$poController = new ProductionOrderController();
$poController->checkMaterial($po);
$poController->startProduction($po);
$poController->finishProduction($po);

$fgStock = FgInventory::where('gci_part_id', $gciFgPart->id)->value('qty_on_hand');
$rmStockProduction = GciInventory::where('gci_part_id', $gciRmPart->id)->value('on_hand');
echo "    - FG Stock Created: $fgStock, RM Stock Remaining: $rmStockProduction\n";

echo "--- [STEP 4] OUTGOING FLOW ---\n";

$customer = Customer::create(['name' => 'TEST CUSTOMER', 'code' => 'C-TEST', 'status' => 'active', 'currency' => 'USD']);
$dn = DeliveryNote::create(['dn_no' => 'DN-001', 'customer_id' => $customer->id, 'delivery_date' => now(), 'status' => 'draft']);
DnItem::create(['dn_id' => $dn->id, 'gci_part_id' => $gciFgPart->id, 'qty' => 4]);

$dnController = new DeliveryNoteController();
try {
    $dnController->ship($dn);
} catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
    // Redirection caught
} catch (\Throwable $e) {
    echo "    - Shipment Exception (Continuing): " . $e->getMessage() . "\n";
}

$fgFinal = FgInventory::where('gci_part_id', $gciFgPart->id)->value('qty_on_hand');
echo "    - Final FG Stock: $fgFinal (Expected: 6)\n";

if ($fgFinal == 6) {
    echo "--- E2E FLOW VERIFIED SUCCESSFULLY ---\n";
} else {
    echo "--- E2E FLOW VERIFICATION FAILED! ---\n";
    exit(1);
}
