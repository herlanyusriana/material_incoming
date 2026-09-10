<?php

namespace Tests\Feature;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

/**
 * UOM package + Fase 2 (auto-post RECEIVING):
 *  - GciPart.uom (stok material) dipakai sebagai satu-satunya UOM stok.
 *  - QC pass → stok terbentuk di lokasi virtual RECEIVING (idempoten).
 *  - Putaway memindah saldo RECEIVING -> rak (tidak dobel).
 *  - Guard COIL: receive COIL hanya boleh untuk part ber-UOM KGM.
 */
class UomAndReceivingFlowTest extends TestCase
{
    use CreatesNewSchemaData;
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_qc_pass_auto_posts_stock_to_receiving(): void
    {
        $user = $this->makeAdmin();

        $gciPart = $this->makeNewGciPart('QA-UOM-001', 'RM');
        $gciPart->update(['uom' => 'KGM']);
        $vendorPart = $this->makeNewVendorPart($this->makeNewVendor()->id, $gciPart->id);
        $arrivalItem = $this->makeNewArrivalItem(
            $this->makeNewArrival($vendorPart->vendor_id)->id,
            $gciPart->id,
            $vendorPart->id,
            100,
            'KGM'
        );

        $receive = IncomingReceive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'QA-UOM-TAG-1',
            'qty' => 100,
            'qty_unit' => 'KGM',
            'qc_status' => 'hold',
            'ata_date' => now(),
        ]);

        // QC pass via controller → auto-post ke RECEIVING.
        $this->actingAs($user)
            ->post("/warehouse/qc/{$receive->id}", ['qc_status' => 'pass'])
            ->assertSessionHas('success');

        $stock = InventoryLocationStock::where('gci_part_id', $gciPart->id)
            ->where('location_code', 'RECEIVING')
            ->first();

        $this->assertNotNull($stock, 'stok QC-pass harus terbentuk di RECEIVING');
        $this->assertSame(100.0, (float) $stock->qty_on_hand);
        $this->assertSame('KGM', $stock->uom, 'UOM stok harus dari gci_parts.uom');
        $this->assertSame('RECEIVING', strtoupper((string) $receive->fresh()->location_code));
    }

    public function test_qc_pass_auto_post_is_idempotent(): void
    {
        $user = $this->makeAdmin();

        $gciPart = $this->makeNewGciPart('QA-UOM-002', 'RM');
        $gciPart->update(['uom' => 'PCE']);
        $vendorPart = $this->makeNewVendorPart($this->makeNewVendor()->id, $gciPart->id);
        $arrivalItem = $this->makeNewArrivalItem(
            $this->makeNewArrival($vendorPart->vendor_id)->id,
            $gciPart->id,
            $vendorPart->id,
            10,
            'PCS'
        );

        $receive = IncomingReceive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'QA-UOM-TAG-2',
            'qty' => 10,
            'qty_unit' => 'PCS',
            'qc_status' => 'hold',
            'ata_date' => now(),
        ]);

        $this->actingAs($user)->post("/warehouse/qc/{$receive->id}", ['qc_status' => 'pass']);
        $this->actingAs($user)->post("/warehouse/qc/{$receive->id}", ['qc_status' => 'pass']);

        $total = (float) InventoryLocationStock::where('gci_part_id', $gciPart->id)
            ->where('location_code', 'RECEIVING')
            ->sum('qty_on_hand');

        $this->assertSame(10.0, $total, 'QC pass dobel tidak boleh dobel posting');
    }

    public function test_receive_pass_posts_to_receiving_before_putaway_even_when_rack_is_entered(): void
    {
        $user = $this->makeAdmin();

        $gciPart = $this->makeNewGciPart('QA-UOM-005', 'RM');
        $gciPart->update(['uom' => 'PCE']);
        $vendorPart = $this->makeNewVendorPart($this->makeNewVendor()->id, $gciPart->id);
        $arrivalItem = $this->makeNewArrivalItem(
            $this->makeNewArrival($vendorPart->vendor_id)->id,
            $gciPart->id,
            $vendorPart->id,
            10,
            'PCS'
        );
        WarehouseLocation::create(['location_code' => 'QA-RACK-DEFERRED', 'status' => 'active']);

        $this->actingAs($user)
            ->post("/departure-items/{$arrivalItem->id}/receive", [
                'receive_date' => '2026-09-10',
                'tags' => [[
                    'tag' => 'QA-UOM-TAG-5',
                    'qty' => 10,
                    'bundle_unit' => 'BOX',
                    'bundle_qty' => 1,
                    'qty_unit' => 'PCS',
                    'qc_status' => 'pass',
                    'location_code' => 'QA-RACK-DEFERRED',
                ]],
            ])
            ->assertSessionMissing('errors');

        $receive = IncomingReceive::where('tag', 'QA-UOM-TAG-5')->firstOrFail();
        $receiving = InventoryLocationStock::where('gci_part_id', $gciPart->id)
            ->where('location_code', 'RECEIVING')
            ->first();

        $this->assertNotNull($receiving, 'PASS receive harus langsung tersimpan di RECEIVING');
        $this->assertSame(10.0, (float) $receiving->qty_on_hand);
        $this->assertNull(
            InventoryLocationStock::where('gci_part_id', $gciPart->id)
                ->where('location_code', 'QA-RACK-DEFERRED')
                ->first(),
            'rak tidak boleh terisi sebelum Putaway'
        );
        $this->assertSame('RECEIVING', strtoupper((string) $receive->location_code));
    }

    public function test_putaway_moves_stock_from_receiving_to_rack(): void
    {
        $user = $this->makeAdmin();

        $gciPart = $this->makeNewGciPart('QA-UOM-003', 'RM');
        $gciPart->update(['uom' => 'PCE']);
        $vendorPart = $this->makeNewVendorPart($this->makeNewVendor()->id, $gciPart->id);
        $arrivalItem = $this->makeNewArrivalItem(
            $this->makeNewArrival($vendorPart->vendor_id)->id,
            $gciPart->id,
            $vendorPart->id,
            25,
            'PCS'
        );

        $receive = IncomingReceive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'QA-UOM-TAG-3',
            'qty' => 25,
            'qty_unit' => 'PCS',
            'qc_status' => 'pass',
            'ata_date' => now(),
        ]);

        WarehouseQcControllerAlias::postReceivingStock($receive);

        WarehouseLocation::create(['location_code' => 'QA-RACK-01', 'status' => 'active']);

        $this->actingAs($user)
            ->post("/warehouse/putaway/{$receive->id}", ['location_code' => 'QA-RACK-01'])
            ->assertSessionHas('success');

        $receiving = (float) InventoryLocationStock::where('gci_part_id', $gciPart->id)
            ->where('location_code', 'RECEIVING')->sum('qty_on_hand');
        $rack = InventoryLocationStock::where('gci_part_id', $gciPart->id)
            ->where('location_code', 'QA-RACK-01')->first();

        $this->assertSame(0.0, $receiving, 'RECEIVING harus kosong setelah putaway');
        $this->assertNotNull($rack, 'stok harus pindah ke rak');
        $this->assertSame(25.0, (float) $rack->qty_on_hand);
        $this->assertSame('QA-RACK-01', strtoupper((string) $receive->fresh()->location_code));
    }

    public function test_coil_receive_rejected_for_non_kgm_part_on_putaway(): void
    {
        $user = $this->makeAdmin();

        $gciPart = $this->makeNewGciPart('QA-UOM-004', 'RM');
        $gciPart->update(['uom' => 'PCE']); // part PCS, receive dalam COIL → salah UOM
        $vendorPart = $this->makeNewVendorPart($this->makeNewVendor()->id, $gciPart->id);
        $arrivalItem = $this->makeNewArrivalItem(
            $this->makeNewArrival($vendorPart->vendor_id)->id,
            $gciPart->id,
            $vendorPart->id,
            2,
            'COIL'
        );

        $receive = IncomingReceive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'QA-UOM-TAG-4',
            'qty' => 2,
            'qty_unit' => 'COIL',
            'net_weight' => 500,
            'qc_status' => 'pass',
            'ata_date' => now(),
        ]);

        WarehouseLocation::create(['location_code' => 'QA-RACK-02', 'status' => 'active']);

        $this->actingAs($user)
            ->post("/warehouse/putaway/{$receive->id}", ['location_code' => 'QA-RACK-02'])
            ->assertSessionHas('error');

        // Tidak ada stok yang terbentuk di rak.
        $this->assertNull(
            InventoryLocationStock::where('gci_part_id', $gciPart->id)
                ->where('location_code', 'QA-RACK-02')->first()
        );
    }
}

class WarehouseQcControllerAlias extends \App\Http\Controllers\WarehouseQcController
{
}
