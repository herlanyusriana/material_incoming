<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Production\ProductionWorkOrder;
use App\Models\NewSchema\Production\WoMaterialAllocation;
use App\Models\User;
use App\Services\WoTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

/**
 * WO Material Tracking E2E — skenario atasan:
 * WO 14.000 PCE FG -> demand 1.400 KGM RM -> scan coil tags (FIFO, minimal
 * split) -> alokasi terkunci -> hasil diposting -> WO tidak bisa closed
 * selama ada alokasi RESERVED.
 */
class WoMaterialTrackingTest extends TestCase
{
    use CreatesNewSchemaData;
    use RefreshDatabase;

    private WoTrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WoTrackingService::class);
        User::factory()->create(['role' => 'admin']);
        $this->actingAs(User::where('role', 'admin')->first());
    }

    private function makeScenario(): array
    {
        // FG: 14.000 PCE, RM: 0,1 KGM/PCE -> 1.400 KGM
        $fg = $this->makeNewGciPart('QA-WO-FG', 'FG');
        $fg->update(['uom' => 'PCE', 'default_location' => 'QA-FG-LOC']);

        $rm = $this->makeNewGciPart('QA-WO-RM', 'RM');
        $rm->update(['uom' => 'KGM']);

        $bom = Bom::create(['part_id' => $fg->id, 'status' => 'active']);
        BomItem::create([
            'bom_id' => $bom->id,
            'component_part_id' => $rm->id,
            'component_part_no' => $rm->part_no,
            'usage_qty' => 0.1,
            'consumption_uom' => 'KGM',
            'make_or_buy' => 'make',
            'yield_factor' => 1,
            'scrap_factor' => 0,
        ]);

        // Tag coil di 3 lokasi seperti contoh: WH1=2000/4, WH2=500/1, WH3=1500/3
        WarehouseLocation::create(['location_code' => 'WH1', 'status' => 'active']);
        WarehouseLocation::create(['location_code' => 'WH2', 'status' => 'active']);
        WarehouseLocation::create(['location_code' => 'WH3', 'status' => 'active']);
        WarehouseLocation::create(['location_code' => 'QA-FG-LOC', 'status' => 'active']);

        $vendor = $this->makeNewVendor();
        $vendorPart = $this->makeNewVendorPart($vendor->id, $rm->id);
        $arrival = $this->makeNewArrival($vendor->id);
        $arrivalItem = $this->makeNewArrivalItem($arrival->id, $rm->id, $vendorPart->id, 9000, 'KGM');

        $tags = [];
        foreach (['WH1' => 4, 'WH2' => 1, 'WH3' => 3] as $loc => $coils) {
            for ($i = 1; $i <= $coils; $i++) {
                $tag = strtoupper("TAG-{$loc}-{$i}");
                IncomingReceive::create([
                    'arrival_item_id' => $arrivalItem->id,
                    'tag' => $tag,
                    'qty' => 500,
                    'qty_unit' => 'KGM',
                    'qc_status' => 'pass',
                    'ata_date' => now()->subDays(10 - strlen($loc)),
                ]);
                InventoryLocationStock::create([
                    'gci_part_id' => $rm->id,
                    'location_code' => $loc,
                    'batch_no' => $tag,
                    'qty_on_hand' => 500,
                    'uom' => 'KGM',
                ]);
                $tags[] = $tag;
            }
        }

        return [$fg, $rm, $bom, $tags];
    }

    private function makeWo(int $fgId, float $qty): ProductionWorkOrder
    {
        $wo = ProductionWorkOrder::create([
            'work_order_no' => 'WO-TEST-0001',
            'gci_part_id' => $fgId,
            'qty_target' => $qty,
            'status' => 'PLANNED',
            'start_date' => now()->toDateString(),
            'created_by' => 1,
        ]);
        $this->service->buildRequirements($wo);

        return $wo->fresh();
    }

    public function test_explosion_14000_pcs_becomes_1400_kgm(): void
    {
        [$fg, $rm] = $this->makeScenario();
        $wo = $this->makeWo($fg->id, 14000);

        $this->assertSame(1, $wo->requirements->count());
        $requirement = $wo->requirements->first();
        $this->assertSame(1400.0, (float) $requirement->required_qty);
        $this->assertSame('KGM', $requirement->uom);
    }

    public function test_suggestion_is_fifo_with_minimal_split(): void
    {
        [$fg, $rm] = $this->makeScenario();
        $wo = $this->makeWo($fg->id, 14000);
        $requirement = $wo->requirements->first();

        $suggestion = $this->service->suggestPicks($wo, $requirement);

        // FIFO: WH1 (age paling lama karena -10 hari vs -9 vs -8).
        $picks = $suggestion['picks']->values();
        $this->assertSame('TAG-WH1-1', $picks[0]['tag']);
        $this->assertSame('TAG-WH1-2', $picks[1]['tag']);
        $this->assertSame('TAG-WH1-3', $picks[2]['tag']);
        $this->assertSame(400.0, (float) $picks[2]['qty']); // split terakhir
        $this->assertSame(0.0, $suggestion['shortfall']);
    }

    public function test_suggestion_uses_only_stocked_substitutes_in_fifo_order(): void
    {
        [$fg, $rm, $bom] = $this->makeScenario();
        $sub = $this->makeNewGciPart('QA-WO-SUB', 'RM');
        $sub->update(['uom' => 'KGM']);
        $bomItem = $bom->items()->first();
        BomItemSubstitute::create([
            'bom_item_id' => $bomItem->id,
            'substitute_part_id' => $sub->id,
            'ratio' => 1,
            'priority' => 1,
            'status' => 'active',
        ]);

        foreach (['TAG-SUB-OLD' => 500, 'TAG-SUB-NEW' => 500] as $tag => $qty) {
            IncomingReceive::create([
                'arrival_item_id' => IncomingReceive::where('tag', 'TAG-WH1-1')->value('arrival_item_id'),
                'tag' => $tag,
                'qty' => $qty,
                'qty_unit' => 'KGM',
                'qc_status' => 'pass',
                'ata_date' => $tag === 'TAG-SUB-OLD' ? now()->subDays(5) : now()->subDay(),
            ]);
            InventoryLocationStock::create([
                'gci_part_id' => $sub->id,
                'location_code' => 'RECEIVING',
                'batch_no' => $tag,
                'qty_on_hand' => $qty,
                'uom' => 'KGM',
            ]);
        }

        $wo = $this->makeWo($fg->id, 14000);
        $suggestion = $this->service->suggestPicks($wo, $wo->requirements->first());

        $this->assertSame('TAG-SUB-OLD', $suggestion['picks']->first()['tag']);
        $this->assertSame($sub->id, $suggestion['picks']->first()['gci_part_id']);
        $this->assertSame(0.0, $suggestion['shortfall']);
    }

    public function test_allocate_locks_tag_and_releases_wo(): void
    {
        [$fg, $rm] = $this->makeScenario();
        $wo = $this->makeWo($fg->id, 14000);
        $requirement = $wo->requirements->first();

        $this->service->allocate($wo, $requirement, 'TAG-WH1-1', 500, 'WH1');
        $this->service->allocate($wo, $requirement, 'TAG-WH1-2', 500, 'WH1');
        $this->service->allocate($wo, $requirement, 'TAG-WH1-3', 400, 'WH1');

        $wo = $wo->fresh();
        $this->assertSame('RELEASED', $wo->status, 'Material lengkap -> WO release');
        $this->assertSame(3, $wo->allocations->count());

        // Guard closure: alokasi masih RESERVED (backflush) -> tidak bisa closed.
        $this->assertTrue($wo->hasOpenAllocations());
    }

    public function test_double_allocation_across_wo_is_rejected(): void
    {
        [$fg, $rm] = $this->makeScenario();
        $wo1 = $this->makeWo($fg->id, 14000);
        $requirement1 = $wo1->requirements->first();

        // WO kedua 7.000 PCE = butuh 700 KGM.
        $wo2 = ProductionWorkOrder::create([
            'work_order_no' => 'WO-TEST-0002',
            'gci_part_id' => $fg->id,
            'qty_target' => 7000,
            'status' => 'PLANNED',
            'start_date' => now()->toDateString(),
            'created_by' => 1,
        ]);
        $this->service->buildRequirements($wo2);

        $this->service->allocate($wo1, $requirement1, 'TAG-WH1-1', 500, 'WH1');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->allocate($wo2, $wo2->requirements->first(), 'TAG-WH1-1', 500, 'WH1');
    }

    public function test_backflush_consumes_from_allocations_and_leftover_return(): void
    {
        [$fg, $rm] = $this->makeScenario();
        $wo = $this->makeWo($fg->id, 14000);
        $requirement = $wo->requirements->first();

        // Alokasi lebih (full coil 500 x3 = 1500 > 1400) — over-allocation.
        $this->service->allocate($wo, $requirement, 'TAG-WH1-1', 500, 'WH1');
        $this->service->allocate($wo, $requirement, 'TAG-WH1-2', 500, 'WH1');
        $this->service->allocate($wo, $requirement, 'TAG-WH1-3', 500, 'WH1');

        // Posting hasil penuh: good 14.000 -> konsumsi 1.400 dari alokasi.
        $response = $this->post("/production/wo-tracking/{$wo->id}/result", [
            'qty_good' => 14000,
            'qty_ng' => 0,
        ]);
        $response->assertSessionMissing('error');

        // Alokasi 1&2 full consumed; alokasi 3 sisa open 100.
        $a1 = WoMaterialAllocation::where('tag', 'TAG-WH1-1')->first();
        $a2 = WoMaterialAllocation::where('tag', 'TAG-WH1-2')->first();
        $a3 = WoMaterialAllocation::where('tag', 'TAG-WH1-3')->first();
        $this->assertSame('CONSUMED', $a1->status);
        $this->assertSame(500.0, (float) $a1->qty_consumed);
        $this->assertSame('CONSUMED', $a2->status);
        $this->assertSame('RESERVED', $a3->status);
        $this->assertSame(400.0, (float) $a3->qty_consumed);
        $this->assertSame(100.0, $a3->openQty());

        // WO masih tidak bisa closed (sisa 100 belum return).
        $close = $this->post("/production/wo-tracking/{$wo->id}/close");
        $close->assertSessionHas('error');

        // Return sisa -> stok WH1 tag3: 500 - 400 (consume) + 100 (return) = 200.
        $this->post("/production/wo-tracking/{$wo->id}/allocations/{$a3->id}/deallocate");
        $stockWh1Tag3 = (float) InventoryLocationStock::where('location_code', 'WH1')
            ->where('batch_no', 'TAG-WH1-3')->sum('qty_on_hand');
        $this->assertSame(200.0, $stockWh1Tag3);

        $this->post("/production/wo-tracking/{$wo->id}/close")->assertSessionHas('success');
        $this->assertSame('CLOSED', $wo->fresh()->status);

        // RM net: WH1 = 2000 awal - 1400 konsumsi + 100 return = 700
        // (tag1=0, tag2=0, tag3=200, tag4=500 tak tersentuh).
        $this->assertSame(700.0, (float) InventoryLocationStock::where('gci_part_id', $rm->id)->where('location_code', 'WH1')->sum('qty_on_hand'));
        $fgStock = InventoryLocationStock::where('gci_part_id', $fg->id)->where('batch_no', 'WO-TEST-0001')->first();
        $this->assertNotNull($fgStock);
        $this->assertSame(14000.0, (float) $fgStock->qty_on_hand);
    }
}
