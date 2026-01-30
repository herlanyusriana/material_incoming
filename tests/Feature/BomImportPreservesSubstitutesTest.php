<?php

namespace Tests\Feature;

use App\Imports\BomImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BomImportPreservesSubstitutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_updates_by_line_no_and_keeps_substitutes(): void
    {
        $fg = GciPart::create([
            'part_no' => 'FG-001',
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

        // Current migrations still keep bom_items.component_part_id FK to `parts` on sqlite.
        // Insert a matching `parts` row so the FK passes in tests.
        DB::table('parts')->insert([
            'id' => $rm->id,
            'part_no' => $rm->part_no,
            'part_name_gci' => $rm->part_name,
            'status' => 'active',
            'uom' => 'PCS',
            'quality_inspection' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'line_no' => 1,
            'component_part_id' => $rm->id,
            'component_part_no' => $rm->part_no,
            'material_name' => 'OLD MATERIAL',
            'usage_qty' => 1,
        ]);

        BomItemSubstitute::create([
            'bom_item_id' => $bomItem->id,
            'substitute_part_id' => $sub->id,
            'substitute_part_no' => $sub->part_no,
            'ratio' => 1,
            'priority' => 1,
            'status' => 'active',
        ]);

        $import = new BomImport();

        // Change fields that were previously part of the "signature" so the import would create a new line,
        // which makes substitutes look like they disappeared (they stay on the old bom_item_id).
        $import->model([
            'no' => 1,
            'fg_part_no' => 'FG-001',
            'rm_part_no' => 'RM-001',
            'material_name' => 'NEW MATERIAL',
            'consumption' => 2,
            'make_or_buy' => 'buy',
        ]);

        $this->assertSame(1, BomItem::query()->where('bom_id', $bom->id)->count());

        $updated = BomItem::query()->where('bom_id', $bom->id)->where('line_no', 1)->firstOrFail();
        $this->assertSame($bomItem->id, $updated->id);
        $this->assertSame('NEW MATERIAL', $updated->material_name);

        $this->assertTrue(
            BomItemSubstitute::query()
                ->where('bom_item_id', $updated->id)
                ->where('substitute_part_id', $sub->id)
                ->exists()
        );
    }
}
