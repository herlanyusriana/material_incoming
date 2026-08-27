<?php
// QA-014 verification seed — drives the REAL controllers against erp_gci_new (insert-only).
// NO migrate:fresh. Creates QA-marked data so the user can see the incoming->inventory
// flow in the running app, then the data is removed afterward.
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\NewSchema\Core\Vendor;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\VendorPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$RUN = '20260825';
$invoiceNo = "QA-INV-E2E-{$RUN}-001";
$loc = "QA-E2E-{$RUN}-LOC";
$tag = "QA-TAG-{$RUN}";

// Helper to build a Laravel Request that supports ->validate() (macro via global validator()).
function qa_req(array $payload, string $uri): Request {
    $req = Request::create($uri, 'POST', $payload);
    try { $req->setUserResolver(function () { return Auth::user(); }); } catch (\Throwable $e) {}
    return $req;
}

try {
    echo "CONNECT: ".config('database.connections.mysql.database')."\n";
    if (config('database.connections.mysql.database') !== 'erp_gci_new') {
        echo "ABORT: bukan erp_gci_new — berhenti.\n"; exit(1);
    }

    // Idempotent: purge any prior QA-{$RUN} arrival chain (hard delete to bypass soft-delete).
    $prevArrivals = IncomingArrival::where('invoice_no', 'LIKE', "QA-INV-E2E-{$RUN}-%")->pluck('id');
    if ($prevArrivals->isNotEmpty()) {
        $prevItems = IncomingArrivalItem::whereIn('arrival_id', $prevArrivals)->pluck('id');
        IncomingReceive::whereIn('arrival_item_id', $prevItems)->get()->each->forceDelete();
        IncomingArrivalItem::whereIn('arrival_id', $prevArrivals)->get()->each->forceDelete();
        IncomingArrival::whereIn('id', $prevArrivals)->get()->each->forceDelete();
        echo "PURGED {$prevArrivals->count()} prior QA-{$RUN} arrival(s)\n";
    }

    // --- Master data (mock): create once; skip if already present ---
    $vendor = Vendor::firstOrCreate(
        ['vendor_name' => "QA-E2E-{$RUN} Vendor"],
        ['vendor_type' => 'import', 'status' => 'active']
    );
    $gciPart = GciPart::firstOrCreate(
        ['part_no' => "QA-E2E-{$RUN}-PART"],
        ['part_name' => "QA E2E Part {$RUN}", 'classification' => 'RM', 'status' => 'active']
    );
    $vendorPart = VendorPart::firstOrCreate(
        ['vendor_part_no' => "QA-VP-{$RUN}"],
        ['gci_part_id' => $gciPart->id, 'vendor_id' => $vendor->id,
         'vendor_part_name' => "QA-VP-{$RUN}", 'quality_inspection' => false, 'status' => 'active']
    );
    WarehouseLocation::firstOrCreate(
        ['location_code' => $loc], ['status' => 'active']
    );
    echo "MASTER: vendor#{$vendor->id} gciPart#{$gciPart->id} vendorPart#{$vendorPart->id} loc={$loc}\n";

    // Auth as existing admin (id=1)
    $admin = User::where('role', 'admin')->first();
    if (!$admin) { echo "ABORT: tidak ada admin.\n"; exit(1); }
    Auth::loginUsingId($admin->id);
    echo "AUTH: user#{$admin->id} ({$admin->name})\n\n";

    // 1) DEPARTURE — ArrivalController::store (this is the Q-014 fixed path)
    $arrivalCtl = app(\App\Http\Controllers\ArrivalController::class);
    $depReq = qa_req([
        'invoice_no'   => $invoiceNo,
        'invoice_date' => '2026-08-25',
        'vendor_id'    => $vendor->id,
        'currency'     => 'USD',
        'items'        => [[
            'part_id'      => $vendorPart->id,
            'qty_goods'    => 10,
            'unit_goods'   => 'PCS',
            'weight_nett'  => 100,
            'weight_gross' => 120,
            'total_amount' => 500,
        ]],
    ], '/departures');
    $arrivalCtl->store($depReq);
    $arrival = IncomingArrival::where('invoice_no', $invoiceNo)->firstOrFail();
    $item = $arrival->items()->where('gci_part_id', $gciPart->id)->first();
    echo "1) DEPARTURE: #{$arrival->id} {$arrival->arrival_no} invoice={$invoiceNo} item#{$item->id}\n";

    // 2) RECEIVE — ReceiveController::store (defer putaway: no location_code yet)
    $recvCtl = app(\App\Http\Controllers\ReceiveController::class);
    $recvReq = qa_req([
        'receive_date' => '2026-08-25',
        'tags' => [[
            'tag'         => $tag,
            'qty'         => 10,
            'bundle_unit' => 'BOX',
            'qty_unit'    => 'PCS',
            'qc_status'   => 'pass',
            'net_weight'  => 100,
        ]],
    ], "/departure-items/{$item->id}/receive");
    $recvCtl->store($recvReq, $item);
    $receive = IncomingReceive::where('tag', $tag)->where('arrival_item_id', $item->id)->first();
    echo "2) RECEIVE: #{$receive->id} qc_status={$receive->qc_status} qty={$receive->qty} tag={$receive->tag}\n";

    // 3) PUTAWAY — WarehousePutawayController::store -> inventory lands
    $putCtl = app(\App\Http\Controllers\WarehousePutawayController::class);
    $putReq = qa_req(['location_code' => $loc], "/warehouse/putaway/{$receive->id}");
    $putCtl->store($putReq, $receive);

    $stock = InventoryLocationStock::where('gci_part_id', $gciPart->id)
        ->where('location_code', $loc)->first();
    echo "3) PUTAWAY: stock#".($stock->id ?? 'NULL')." qty_on_hand=".($stock->qty_on_hand ?? 'NULL')." @{$loc}\n";

    echo "\nDONE. Ke data:\n";
    echo "   Departure: ".route('departures.show', $arrival)."\n";
    echo "   Inventory gci_part=$gciPart->id loc=$loc\n";
    echo "   Invoice: $invoiceNo | Tag: $tag\n";
} catch (\Throwable $e) {
    echo "\nERROR: ".get_class($e).": ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}
