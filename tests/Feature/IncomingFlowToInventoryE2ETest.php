<?php

namespace Tests\Feature;

use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

/**
 * Full E2E of the "incoming -> inventory" flow through the REAL controllers (HTTP).
 *   ArrivalController::store              -> create departure / incoming
 *   ReceiveController::store              -> scan / receive an arrival item
 *   WarehousePutawayController::store     -> putaway to a location (stock lands)
 *
 * STATUS: REGRESSION TEST — currently RED because of QA-014 (HIGH):
 *   ArrivalController::store writes `part_id` / `gci_part_vendor_id` to
 *   `incoming_arrival_items`, but the table only has a `vendor_part_id` column
 *   (both in prod erp_gci_new and in migrations). Every departure create with an
 *   item throws SQLSTATE[42S22] Unknown column 'part_id' -> HTTP 500, so the
 *   incoming -> inventory flow is blocked at the FIRST step.
 *
 * Expected to turn GREEN once QA-014 is fixed in the ArrivalController refactor
 * (write `vendor_part_id` instead of `part_id` / `gci_part_vendor_id`, and update
 * the `->part_id` read sites to `->vendor_part_id`).
 */
class IncomingFlowToInventoryE2ETest extends TestCase
{
    use CreatesNewSchemaData;
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        // Gate::before grants every permission to role=admin (AppServiceProvider)
        return User::factory()->create(['role' => 'admin']);
    }

    /** POST /departures with a valid single-item payload. */
    private function postDeparture(User $user, int $vendorId, int $vendorPartId, string $invoiceNo)
    {
        return $this->actingAs($user)->post('/departures', [
            'invoice_no'   => $invoiceNo,
            'invoice_date' => '2026-08-25',
            'vendor_id'    => $vendorId,
            'currency'     => 'USD',
            'items'        => [
                [
                    'part_id'      => $vendorPartId,
                    'qty_goods'    => 10,
                    'unit_goods'   => 'PCS',
                    'weight_nett'  => 100,
                    'weight_gross' => 120,
                    'total_amount' => 500,
                ],
            ],
        ]);
    }

    public function test_full_incoming_flow_lands_received_qty_in_inventory(): void
    {
        $user = $this->makeAdmin();

        // --- Master data (vendor + gci part + vendor part + warehouse location) ---
        $vendor     = $this->makeNewVendor('QA E2E Vendor');
        $gciPart    = $this->makeNewGciPart('QA-E2E-001', 'RM', 'QA E2E Part');
        $vendorPart = $this->makeNewVendorPart($vendor->id, $gciPart->id, 'QA-VP-001');

        WarehouseLocation::create(['location_code' => 'QA-E2E-01', 'status' => 'active']);

        // 1) Departure / incoming — ArrivalController::store
        $dep = $this->postDeparture($user, $vendor->id, $vendorPart->id, 'QA-INV-E2E-0001');
        $dep->assertStatus(302);
        $dep->assertSessionMissing('errors');

        $arrival = IncomingArrival::where('invoice_no', 'QA-INV-E2E-0001')->firstOrFail();
        $arrivalItem = IncomingArrivalItem::where('gci_part_id', $gciPart->id)->firstOrFail();
        $this->assertSame(10, (int) $arrivalItem->qty_goods);

        // 2) Receive — ReceiveController::store (defer putaway: no location yet)
        $rcv = $this->actingAs($user)->post("/departure-items/{$arrivalItem->id}/receive", [
            'receive_date' => '2026-08-25',
            'tags' => [
                [
                    'tag'         => 'QA-TAG-0001',
                    'qty'         => 10,
                    'bundle_unit' => 'BOX',
                    'qty_unit'    => 'PCS',
                    'qc_status'   => 'pass',
                    'net_weight'  => 100,
                ],
            ],
        ]);
        $rcv->assertSessionMissing('errors');

        $receive = IncomingReceive::where('tag', 'QA-TAG-0001')->firstOrFail();
        $this->assertSame('pass', $receive->qc_status);
        $this->assertSame(10, (int) $receive->qty);

        // No stock yet (receive did not putaway)
        $this->assertNull(
            InventoryLocationStock::where('gci_part_id', $gciPart->id)
                ->where('location_code', 'QA-E2E-01')->first(),
            'stock must not exist before putaway'
        );

        // 3) Putaway — WarehousePutawayController::store
        $this->actingAs($user)
            ->post("/warehouse/putaway/{$receive->id}", ['location_code' => 'QA-E2E-01'])
            ->assertSessionHas('success');

        // 4) Inventory landed
        $loc = InventoryLocationStock::where('gci_part_id', $gciPart->id)
            ->where('location_code', 'QA-E2E-01')->first();
        $this->assertNotNull($loc, 'inventory stock row must exist after putaway');
        $this->assertSame(10.0, (float) $loc->qty_on_hand, 'inventory qty must equal received qty');
    }

    public function test_incoming_flow_rejects_qc_fail_from_putaway(): void
    {
        $user = $this->makeAdmin();

        $vendor     = $this->makeNewVendor('QA E2E Vendor 2');
        $gciPart    = $this->makeNewGciPart('QA-E2E-002', 'RM', 'QA E2E Part 2');
        $vendorPart = $this->makeNewVendorPart($vendor->id, $gciPart->id, 'QA-VP-002');

        WarehouseLocation::create(['location_code' => 'QA-E2E-02', 'status' => 'active']);

        // 1) Departure
        $dep = $this->postDeparture($user, $vendor->id, $vendorPart->id, 'QA-INV-E2E-0002');
        $dep->assertStatus(302);
        $dep->assertSessionMissing('errors');

        $arrivalItem = IncomingArrivalItem::where('gci_part_id', $gciPart->id)->firstOrFail();

        // 2) Receive with qc_status = reject
        $rcv = $this->actingAs($user)->post("/departure-items/{$arrivalItem->id}/receive", [
            'receive_date' => '2026-08-25',
            'tags' => [
                [
                    'tag'         => 'QA-TAG-0002',
                    'qty'         => 10,
                    'bundle_unit' => 'BOX',
                    'qty_unit'    => 'PCS',
                    'qc_status'   => 'reject',
                    'net_weight'  => 100,
                ],
            ],
        ]);
        $rcv->assertSessionMissing('errors');

        $receive = IncomingReceive::where('tag', 'QA-TAG-0002')->firstOrFail();
        $this->assertSame('reject', $receive->qc_status);

        // 3) Putaway on a REJECTED receive must be rejected (no stock created).
        $this->actingAs($user)
            ->post("/warehouse/putaway/{$receive->id}", ['location_code' => 'QA-E2E-02'])
            ->assertSessionHas('error');

        $this->assertNull(
            InventoryLocationStock::where('gci_part_id', $gciPart->id)
                ->where('location_code', 'QA-E2E-02')->first(),
            'no inventory stock for rejected receive'
        );
    }
}
