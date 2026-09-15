<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningBomWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_index_groups_fg_identity_and_links_to_exploded_process_tree(): void
    {
        $user = User::factory()->create();
        [$bom] = $this->makeBomFixture();

        // Index = kartu FG ringkas (tanpa kolom tabel proses, tanpa net weight).
        $this->actingAs($user)
            ->get(route('planning.boms.index', ['gci_part_id' => $bom->part_id]))
            ->assertOk()
            ->assertSeeText('FG-100')
            ->assertSeeText('Finished Assembly')
            ->assertSeeText('MODEL-X')
            ->assertSeeText('Buka Exploder')
            ->assertDontSeeText('FG Net Weight')
            ->assertDontSeeText('12.3456 kg/pcs')
            ->assertDontSeeText('Parent Part No.');

        // Explosion = pohon proses WIP -> RM lengkap dengan identitas part.
        $this->actingAs($user)
            ->get(route('planning.boms.explosion', $bom))
            ->assertOk()
            ->assertSeeText('WIP-100-01')
            ->assertSeeText('Pressed Body')
            ->assertSeeText('Press')
            ->assertSeeText('Press Line 1')
            ->assertSeeText('Steel Coil')
            ->assertSeeText('RM-100')
            ->assertSeeText('MODEL-X');
    }

    public function test_bom_update_does_not_modify_net_weight_on_the_fg_master(): void
    {
        $user = User::factory()->create();
        [$bom] = $this->makeBomFixture();
        $newFg = GciPart::create([
            'part_no' => 'FG-200',
            'part_name' => 'Replacement Assembly',
            'classification' => 'FG',
            'status' => 'active',
            'net_weight' => 4.2500,
        ]);

        $this->actingAs($user)
            ->put(route('planning.boms.update', $bom), [
                'part_id' => $newFg->id,
                'net_weight' => '8.1250',
            ])
            ->assertRedirect();

        $this->assertSame('4.2500', $newFg->fresh()->getRawOriginal('net_weight'));
    }

    public function test_line_editor_can_save_parent_name_and_unit(): void
    {
        $user = User::factory()->create();
        [$bom] = $this->makeBomFixture();
        $item = $bom->items()->firstOrFail();
        $this->actingAs($user)->post(route('planning.boms.items.store', $bom), [
            'bom_item_id' => $item->id,
            'component_part_id' => $item->component_part_id,
            'component_part_no' => $item->component_part_no,
            'wip_part_id' => $item->wip_part_id,
            'wip_part_no' => $item->wip_part_no,
            'wip_part_name' => 'Revised parent name',
            'wip_uom' => 'PCE',
            'usage_qty' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Revised parent name', $item->fresh()->wip_part_name);
        $this->assertSame('PCE', $item->fresh()->wip_uom);
        $this->assertSame('WIP-100-01', $item->fresh()->wip_part_no);
    }

    public function test_import_recognizes_parent_column_headings(): void
    {
        [$bom] = $this->makeBomFixture();
        (new \App\Imports\BomImport())->model([
            'fg_part_no' => 'FG-100', 'seq' => 2, 'no' => 99,
            'parent_part_no' => 'WIP-100-01', 'parent_part_name' => 'Parent from spreadsheet',
            'parent_part_qty' => 1, 'parent_part_uom' => 'PCE',
            'child_part_no' => 'RM-100', 'consumption' => 2, 'uom_rm' => 'PCE',
        ]);
        $item = $bom->items()->where('line_no', 2)->firstOrFail();
        $this->assertSame('Parent from spreadsheet', $item->wip_part_name);
        $this->assertSame('PCE', $item->wip_uom);
    }

    public function test_explosion_renders_when_line_has_no_linked_wip_part(): void
    {
        // Prod break: bom_items.wip_part_id null / part deleted -> belongsTo null,
        // label builder must not dereference the relation directly.
        $user = User::factory()->create();
        [$bom] = $this->makeBomFixture();
        $item = $bom->items()->firstOrFail();
        $item->update(['wip_part_id' => null, 'wip_part_no' => 'WIP-ORPHAN', 'wip_part_name' => 'Orphaned Parent']);

        $this->actingAs($user)
            ->get(route('planning.boms.explosion', $bom))
            ->assertOk()
            ->assertSeeText('WIP-ORPHAN');
    }

    private function makeBomFixture(): array
    {
        $fg = GciPart::create([
            'part_no' => 'FG-100',
            'part_name' => 'Finished Assembly',
            'model' => 'MODEL-X',
            'classification' => 'FG',
            'status' => 'active',
            'net_weight' => 12.3456,
        ]);
        $wip = GciPart::create([
            'part_no' => 'WIP-100-01',
            'part_name' => 'Pressed Body',
            'classification' => 'WIP',
            'status' => 'active',
        ]);
        $rm = GciPart::create([
            'part_no' => 'RM-100',
            'part_name' => 'Steel Coil',
            'classification' => 'RM',
            'status' => 'active',
        ]);
        $machine = Machine::create([
            'code' => 'MC-01',
            'name' => 'Press Line 1',
            'is_active' => true,
        ]);
        $bom = Bom::create([
            'part_id' => $fg->id,
            'revision' => 'A',
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        BomItem::create([
            'bom_id' => $bom->id,
            'line_no' => 1,
            'process_name' => 'Press',
            'machine_id' => $machine->id,
            'wip_part_id' => $wip->id,
            'wip_part_no' => $wip->part_no,
            'wip_part_name' => $wip->part_name,
            'wip_qty' => 1,
            'wip_uom' => 'PCE',
            'component_part_id' => $rm->id,
            'component_part_no' => $rm->part_no,
            'material_name' => $rm->part_name,
            'usage_qty' => 1,
            'consumption_uom' => 'PCE',
        ]);

        return [$bom, $fg];
    }
}
