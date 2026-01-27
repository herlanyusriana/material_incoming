<?php

namespace Tests\Feature;

use App\Imports\BomSubstituteImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BomSubstituteImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_finds_bom_even_if_fg_part_no_has_spaces_in_master_data(): void
    {
        $fg = GciPart::create([
            'part_no' => '4810 JM3004B', // dirty spacing in master data
            'part_name' => 'FG TEST',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $rm = GciPart::create([
            'part_no' => 'RM-001',
            'part_name' => 'RM 001',
            'classification' => 'RM',
            'status' => 'active',
        ]);

        $sub = GciPart::create([
            'part_no' => 'RM-SUB-1',
            'part_name' => 'RM SUB 1',
            'classification' => 'RM',
            'status' => 'active',
        ]);

        $bom = Bom::create([
            'part_id' => $fg->id,
            'revision' => 'A',
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'component_part_no' => $rm->part_no,
            'usage_qty' => 1,
        ]);

        $import = new BomSubstituteImport(false);

        $import->collection(new Collection([
            new Collection([
                'fg_part_no' => '4810JM3004B', // file is normalized (no spaces)
                'component_part_no' => 'RM-001',
                'substitute_part_no' => 'RM-SUB-1',
                'ratio' => 1,
                'priority' => 1,
                'status' => 'active',
                'notes' => null,
            ]),
        ]));

        $this->assertSame([], $import->failures());
        $this->assertSame(1, $import->rowCount);

        $this->assertTrue(
            BomItemSubstitute::query()
                ->where('bom_item_id', $bomItem->id)
                ->where('substitute_part_id', $sub->id)
                ->exists()
        );
    }
}
