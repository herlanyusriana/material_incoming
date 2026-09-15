<?php

namespace Tests\Feature\Production;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\MaterialSubstitute;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Production\ProductionWorkOrder;
use App\Models\NewSchema\Production\WoMaterialAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

class ManualWoMaterialAllocationTest extends TestCase
{
    use CreatesNewSchemaData;
    use RefreshDatabase;

    private User $user;

    private $fg;

    private $genericRm;

    private $substitute;

    private BomItem $bomItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);

        $this->fg = $this->makeNewGciPart('FG-MANUAL-01', 'FG', 'PLATE ASSEMBLY REAR');
        $this->fg->update(['model' => 'AGU', 'uom' => 'PCE']);

        $this->genericRm = $this->makeNewGciPart('RM-GENERIC-01', 'RM', 'BACK PLATE');
        $this->genericRm->update(['uom' => 'KGM']);

        $this->substitute = $this->makeNewGciPart('RM-SUB-01', 'RM', 'BACK PLATE IMPORT');
        $this->substitute->update(['model' => 'SPCC', 'size' => '1.2 X 1219', 'uom' => 'KGM']);

        $bom = Bom::create([
            'part_id' => $this->fg->id,
            'revision' => 'A',
            'effective_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);
        $this->bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'component_part_id' => $this->genericRm->id,
            'component_part_no' => $this->genericRm->part_no,
            'usage_qty' => 0.1,
            'consumption_uom' => 'KGM',
            'make_or_buy' => 'MAKE',
            'line_no' => 1,
        ]);
        $wip = $this->makeNewGciPart('FG-MANUAL-01-WIP1', 'WIP');
        BomItem::create([
            'bom_id' => $bom->id,
            'component_part_id' => $wip->id,
            'component_part_no' => $wip->part_no,
            'usage_qty' => 1,
            'consumption_uom' => 'PCE',
            'make_or_buy' => 'MAKE',
            'line_no' => 2,
        ]);

        $vendor = $this->makeNewVendor('PT STEEL SUPPLIER');
        $vendorPart = $this->makeNewVendorPart($vendor->id, $this->substitute->id, 'SUP-BACK-01');
        $arrival = IncomingArrival::create([
            'arrival_no' => 'ARR-WO-ALLOC-01',
            'invoice_no' => 'INV-STEEL-01',
            'vendor_id' => $vendor->id,
            'status' => 'completed',
        ]);
        $arrivalItem = $this->makeNewArrivalItem($arrival->id, $this->substitute->id, $vendorPart->id, 2000, 'KGM');

        WarehouseLocation::create(['location_code' => 'RM-A01', 'status' => 'active']);
        foreach ([
            ['TAG-OLD', 500, now()->subDays(5)],
            ['TAG-NEW', 1000, now()->subDay()],
        ] as [$tag, $qty, $receivedAt]) {
            IncomingReceive::create([
                'arrival_item_id' => $arrivalItem->id,
                'tag' => $tag,
                'qty' => $qty,
                'qty_unit' => 'KGM',
                'qc_status' => 'pass',
                'ata_date' => $receivedAt,
            ]);
            InventoryLocationStock::create([
                'gci_part_id' => $this->substitute->id,
                'location_code' => 'RM-A01',
                'batch_no' => $tag,
                'qty_on_hand' => $qty,
                'uom' => 'KGM',
            ]);
        }

        MaterialSubstitute::create([
            'generic_part_id' => $this->genericRm->id,
            'substitute_part_id' => $this->substitute->id,
            'vendor_part_id' => $vendorPart->id,
            'ratio' => 1,
            'priority' => 1,
            'status' => 'active',
        ]);
    }

    public function test_allocation_preview_shows_fg_model_and_stocked_substitute_fifo_tags(): void
    {
        // Break caught: the create form falls back to one RM/tag and cannot show BOM allocation details.
        $lockedPart = $this->makeNewGciPart('RM-LOCKED', 'RM', 'RESERVED MATERIAL');
        MaterialSubstitute::create([
            'generic_part_id' => $this->genericRm->id,
            'substitute_part_id' => $lockedPart->id,
            'ratio' => 1,
            'priority' => 2,
            'status' => 'active',
        ]);
        InventoryLocationStock::create([
            'gci_part_id' => $lockedPart->id,
            'location_code' => 'RM-A01',
            'batch_no' => 'TAG-LOCKED',
            'qty_on_hand' => 500,
            'uom' => 'KGM',
        ]);
        $otherWo = ProductionWorkOrder::create([
            'work_order_no' => 'WO-OTHER-01',
            'gci_part_id' => $this->fg->id,
            'qty_target' => 1,
            'start_date' => now()->toDateString(),
            'status' => 'PLANNED',
            'created_by' => $this->user->id,
        ]);
        WoMaterialAllocation::create([
            'work_order_id' => $otherWo->id,
            'gci_part_id' => $lockedPart->id,
            'tag' => 'TAG-LOCKED',
            'location_code' => 'RM-A01',
            'qty_reserved' => 500,
            'status' => WoMaterialAllocation::STATUS_RESERVED,
        ]);

        $response = $this->getJson(route('production.wo-tracking.allocation-preview', [
            'gci_part_id' => $this->fg->id,
            'qty_target' => 14000,
            'start_date' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'requirements')
            ->assertJsonPath('fg.model', 'AGU')
            ->assertJsonPath('requirements.0.bom_item_id', $this->bomItem->id)
            ->assertJsonPath('requirements.0.required_qty', 1400)
            ->assertJsonCount(1, 'requirements.0.substitutes')
            ->assertJsonPath('requirements.0.substitutes.0.part_no', 'RM-SUB-01')
            ->assertJsonPath('requirements.0.substitutes.0.model', 'SPCC')
            ->assertJsonPath('requirements.0.substitutes.0.size', '1.2 X 1219')
            ->assertJsonPath('requirements.0.substitutes.0.supplier', 'PT STEEL SUPPLIER')
            ->assertJsonPath('requirements.0.picks.0.tag', 'TAG-OLD')
            ->assertJsonPath('requirements.0.picks.0.qty', 500)
            ->assertJsonPath('requirements.0.picks.0.invoice_no', 'INV-STEEL-01')
            ->assertJsonPath('requirements.0.picks.1.tag', 'TAG-NEW')
            ->assertJsonPath('requirements.0.picks.1.qty', 900)
            ->assertJsonPath('requirements.0.shortfall', 0);
    }

    public function test_creating_manual_wo_builds_requirements_and_reserves_fifo_tags(): void
    {
        // Break caught: WO is saved without requirement snapshots or material reservations.
        $response = $this->post(route('production.wo-tracking.store'), [
            'gci_part_id' => $this->fg->id,
            'start_date' => now()->toDateString(),
            'qty_target' => 14000,
            'substitute_choices' => [
                $this->bomItem->id => $this->substitute->id,
            ],
        ]);

        $response->assertRedirect(route('production.wo-tracking.index'));

        $wo = ProductionWorkOrder::query()->firstOrFail();
        $this->assertSame('RELEASED', $wo->status);
        $this->assertCount(1, $wo->requirements);
        $this->assertSame(1400.0, (float) $wo->requirements()->sum('required_qty'));
        $this->assertSame(['TAG-OLD', 'TAG-NEW'], $wo->allocations()->orderBy('id')->pluck('tag')->all());
        $this->assertSame([500.0, 900.0], $wo->allocations()->orderBy('id')->pluck('qty_reserved')->map(fn ($qty) => (float) $qty)->all());
        $this->assertNull($wo->main_rm_part_id);
        $this->assertNull($wo->rm_invoice_no);
        $this->assertNull($wo->rm_tag);
    }

    public function test_manual_wo_rejects_a_part_that_is_not_an_active_substitute(): void
    {
        // Break caught: a crafted request can reserve an unrelated material part.
        $unrelated = $this->makeNewGciPart('RM-UNRELATED', 'RM');

        $response = $this->from(route('production.wo-tracking.create'))
            ->post(route('production.wo-tracking.store'), [
                'gci_part_id' => $this->fg->id,
                'start_date' => now()->toDateString(),
                'qty_target' => 100,
                'substitute_choices' => [
                    $this->bomItem->id => $unrelated->id,
                ],
            ]);

        $response->assertRedirect(route('production.wo-tracking.create'))
            ->assertSessionHasErrors('substitute_choices.' . $this->bomItem->id);
        $this->assertDatabaseCount('production_work_orders', 0);
    }

    public function test_create_page_uses_material_allocation_instead_of_single_rm_fields(): void
    {
        // Break caught: operators are forced to choose one invoice and one tag for the whole WO.
        $this->get(route('production.wo-tracking.create'))
            ->assertOk()
            ->assertSeeText('Material Allocation')
            ->assertSeeText('Model FG')
            ->assertSeeText('Rekomendasi FIFO')
            ->assertDontSeeText('RM Invoice')
            ->assertDontSeeText('Main RM Part');
    }
}
