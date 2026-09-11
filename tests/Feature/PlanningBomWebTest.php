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

    public function test_bom_index_groups_fg_identity_and_renders_process_columns_without_fg_net_weight(): void
    {
        $user = User::factory()->create();
        [$bom] = $this->makeBomFixture();

        $this->actingAs($user)
            ->get(route('planning.boms.index', ['gci_part_id' => $bom->part_id]))
            ->assertOk()
            ->assertSeeTextInOrder([
                'Seq',
                'Process Name',
                'Machine Name',
                'Parent Part No.',
                'Parent Part Name',
                'Parent Part Qty',
                'Parent Part UOM',
                'Child Part No.',
                'Child Part Name',
                'Material / Size',
                'Spec / Make-Buy',
            ])
            ->assertDontSeeText('FG Net Weight')
            ->assertDontSeeText('12.3456 kg/pcs')
            ->assertSeeText('FG-100')
            ->assertSeeText('WIP-100-01')
            ->assertSeeText('Pressed Body')
            ->assertSeeText('Steel Coil')
            ->assertSeeText('Finished Assembly')
            ->assertSeeText('MODEL-X')
            ->assertSeeText('RM-100');
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
