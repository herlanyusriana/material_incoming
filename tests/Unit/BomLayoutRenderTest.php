<?php

namespace Tests\Unit;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BomLayoutRenderTest extends TestCase
{
    public function test_process_row_keeps_part_names_and_material_details_visible(): void
    {
        $fg = new GciPart(['part_no' => 'FG-TEST', 'part_name' => 'Finished Assembly', 'model' => 'Model Test']);
        $fg->id = 1;
        $item = new BomItem([
            'line_no' => 1, 'process_name' => 'Press', 'wip_part_no' => 'WIP-TEST',
            'wip_part_name' => 'Pressed Body', 'component_part_no' => 'RM-TEST',
            'material_name' => 'Steel Coil', 'material_size' => '100 x 200',
            'material_spec' => 'SPCC', 'special' => 'Special Test',
            'make_or_buy' => 'buy', 'usage_qty' => 2,
        ]);
        $item->id = 1;
        foreach (['wipPart', 'componentPart', 'incomingPart', 'machine', 'wipUom', 'consumptionUom'] as $relation) {
            $item->setRelation($relation, null);
        }
        $item->setRelation('substitutes', collect());
        $bom = new Bom(['revision' => 'A', 'status' => 'active']);
        $bom->id = 1;
        $bom->setRelation('part', $fg)->setRelation('items', collect([$item]));
        $data = array_fill_keys(['wipParts', 'rmParts', 'makeParts', 'incomingParts', 'uoms', 'machines'], collect());
        $html = view('planning.boms.index', array_merge($data, [
            'boms' => new LengthAwarePaginator([$bom], 1, 20),
            'fgParts' => collect([$fg]), 'gciPartId' => null, 'q' => '',
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]))->render();

        foreach (['Finished Assembly', 'Pressed Body', 'Steel Coil', '100 x 200', 'SPCC', 'Special Test'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringNotContainsString('bom-detail-row', $html);
        $this->assertStringNotContainsString('min-w-[2150px]', $html);
        $this->assertStringNotContainsString('Table density', $html);
        $this->assertStringNotContainsString('density:', $html);
        $this->assertStringNotContainsString('bom-table-comfortable', $html);
        $this->assertStringContainsString('Geser kolom', $html);
        $this->assertStringContainsString('type="range"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('bom-editor-footer', $html);
        $this->assertStringNotContainsString('x-show="lineForm.showAdvanced"', $html);
        $this->assertStringContainsString('Belum diisi', $html);
    }

    public function test_uom_display_uses_explicit_units_then_master_without_inventing_units(): void
    {
        $item = new BomItem();
        $item->setRelation('wipUom', null)->setRelation('consumptionUom', null);
        $item->setRelation('wipPart', null)->setRelation('componentPart', null);
        $this->assertNull($item->display_wip_uom);
        $this->assertNull($item->display_consumption_uom);
        $parent = new GciPart();
        $parent->uom = 'PCE';
        $child = new GciPart();
        $child->uom = 'KGM';
        $item->setRelation('wipPart', $parent)->setRelation('componentPart', $child);
        $this->assertSame('PCE', $item->display_wip_uom);
        $this->assertSame('KGM', $item->display_consumption_uom);
        $item->wip_uom = 'SET';
        $item->consumption_uom = 'MTR';
        $this->assertSame('SET', $item->display_wip_uom);
        $this->assertSame('MTR', $item->display_consumption_uom);
    }
}
