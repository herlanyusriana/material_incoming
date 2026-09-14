<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Models\GciPartVendor;
use App\Models\Machine;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\Vendor;
use App\Models\NewSchema\Core\VendorPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MasterDataWorkbookImporterTest extends TestCase
{
    use RefreshDatabase;

    private string $workbookPath;

    protected function tearDown(): void
    {
        if (isset($this->workbookPath) && is_file($this->workbookPath)) {
            @unlink($this->workbookPath);
        }
        parent::tearDown();
    }

    /**
     * Minimal three-sheet workbook mirroring the real one: headers on row 5.
     */
    private function makeWorkbook(): string
    {
        $spreadsheet = new Spreadsheet();

        $fg = $spreadsheet->getActiveSheet();
        $fg->setTitle('master FG');
        $fg->fromArray(['Part No', 'Part Name', 'Model', 'UOM', 'Size'], null, 'A5');
        $fg->fromArray([
            ['FG-001', 'Bracket Assembly', 'BR-01', 'PCS', '100x50'],
            ['FG-002', 'Cover Panel', 'CV-02', 'PCS', '200x100'],
        ], null, 'A6');

        $mtrl = $spreadsheet->createSheet();
        $mtrl->setTitle('master mtrl');
        $mtrl->fromArray(['Part No', 'Part Name', 'UOM', 'Size', 'Supplier', 'Supplier Part No', 'Generic Part No'], null, 'A5');
        $mtrl->fromArray([
            ['RM-100', 'Steel Plate', 'PCS', '1x2', null, null, null],
            ['RM-200', 'Shaft', 'PCS', null, null, null, null],
            ['SUP-111', 'Steel Plate PT One', 'PCS', '1x2', 'PT. One Steel', 'SUP-111', 'RM-100'],
            ['SUP-112', 'Steel Plate Two', 'PCS', '1x2', 'PT ONE-Steel', 'SUP-112', 'RM-100'],
            ['SUP-221', 'Shaft Jaya', 'PCS', null, 'CV. Jaya', 'SUP-221', 'RM-200'],
        ], null, 'A6');

        $bom = $spreadsheet->createSheet();
        $bom->setTitle('BOM');
        $bom->fromArray(['Parent', 'WIP Part', 'Child', 'Seq', 'Qty', 'UOM', 'Source', 'Machine'], null, 'A5');
        $bom->fromArray([
            ['FG-001', 'WIP-300', 'RM-100', 10, 2, 'PCS', 'MAKE', 'Press 01'],
            ['FG-001', null, 'WIP-300', 20, 1, 'PCS', 'MAKE', 'Asm 01'],
            ['WIP-300', null, 'RM-200', 10, 4, 'PCS', 'BUY', null],
            ['FG-002', null, 'RM-200', 10, 1, 'PCS', 'BUY', null],
        ], null, 'A6');

        $path = tempnam(sys_get_temp_dir(), 'mdwb_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $this->workbookPath = $path;
    }

    /**
     * Workbook using the exact column names from master data 20260908.xlsx.
     */
    private function makeRealFormatWorkbook(): string
    {
        $spreadsheet = new Spreadsheet();

        $fg = $spreadsheet->getActiveSheet();
        $fg->setTitle('master FG');
        $fg->fromArray(['No', 'NAME', 'MODEL', 'Part #'], null, 'A5');
        $fg->fromArray([[1, 'Widget Assembly', 'WD-01', 'FG-100']], null, 'A6');

        $mtrl = $spreadsheet->createSheet();
        $mtrl->setTitle('master mtrl');
        $mtrl->fromArray(
            ['No', 'Name', 'Model', 'Material Part #', 'Subs Part #', 'Size', 'Satuan', 'Supplier', 'Source'],
            null,
            'A5'
        );
        $mtrl->fromArray([
            [1, 'Steel Coil', 'WD-01', 'RM-GENERIC', 'RM-SUPPLIER', '1.0 X 100', 'KGM', 'PT Steel', 'Local'],
        ], null, 'A6');

        $bom = $spreadsheet->createSheet();
        $bom->setTitle('BOM');
        $bom->fromArray([
            'No', 'Seq', 'FG Name', 'FG Model', 'FG Part No.', 'Process Name', 'Machine Name',
            'Parent Part No.', 'Parent Part Name', 'Parent Part Qty', 'Parent Part UOM',
            'Child Part No.', 'Child Part Name', 'Child Part Qty', 'UOM_RM', 'spesial', 'Source',
        ], null, 'A5');
        $bom->fromArray([
            [1, 1, 'Widget Assembly', 'WD-01', 'FG-100', 'Press', 'Press 01', 'FG-100-WIP1', 'Draw', 1, 'PCS', 'RM-GENERIC', 'Steel Coil', 1.5, 'KGM', 'S', 'Vendor'],
            [1, 2, 'Widget Assembly', 'WD-01', 'FG-100', 'Trimming', 'Press 01', 'FG-100-WIP2', 'Trimming', 1, 'PCS', 'FG-100-WIP1', 'Draw', 1, 'PCS', null, 'Prod'],
            [1, 3, 'Widget Assembly', 'WD-01', 'FG-100', 'Assembly', 'Assembly 01', 'FG-100', 'Widget Assembly', 1, 'PCS', 'FG-100-WIP2', 'Trimming', 1, 'PCS', null, 'Prod'],
        ], null, 'A6');

        $path = tempnam(sys_get_temp_dir(), 'mdwb_real_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $this->workbookPath = $path;
    }

    public function test_real_bom_columns_sync_parent_and_child_metadata_to_part_master(): void
    {
        $importer = new \App\Services\MasterDataWorkbookImporter();
        $importer->import($this->makeRealFormatWorkbook());

        $this->assertDatabaseHas('gci_parts', [
            'part_no' => 'FG-100-WIP1',
            'part_name' => 'Draw',
            'uom' => 'PCE',
            'classification' => 'WIP',
        ]);
        $this->assertDatabaseHas('gci_parts', [
            'part_no' => 'FG-100-WIP2',
            'part_name' => 'Trimming',
            'uom' => 'PCE',
            'classification' => 'WIP',
        ]);
        $this->assertDatabaseHas('gci_parts', [
            'part_no' => 'RM-GENERIC',
            'part_name' => 'Steel Coil',
            'uom' => 'KGM',
            'classification' => 'RM',
        ]);
        $this->assertDatabaseHas('gci_parts', [
            'part_no' => 'FG-100',
            'part_name' => 'Widget Assembly',
            'uom' => 'PCE',
            'classification' => 'FG',
        ]);
        $this->assertDatabaseHas('bom_items', [
            'line_no' => 1,
            'wip_part_no' => 'FG-100-WIP1',
            'wip_part_name' => 'Draw',
            'wip_qty' => 1,
            'wip_uom' => 'PCE',
            'component_part_no' => 'RM-GENERIC',
            'consumption_uom' => 'KGM',
        ]);
        $this->assertDatabaseHas('bom_items', [
            'line_no' => 3,
            'wip_part_no' => 'FG-100',
            'wip_part_name' => 'Widget Assembly',
            'wip_qty' => 1,
            'wip_uom' => 'PCE',
            'component_part_no' => 'FG-100-WIP2',
            'consumption_uom' => 'PCE',
        ]);
        $this->assertSame(1, GciPart::where('part_no', 'FG-100-WIP1')->count());
    }

    public function test_replace_parts_removes_obsolete_parts_but_preserves_vendors(): void
    {
        GciPart::create([
            'part_no' => 'OBSOLETE-PART',
            'part_name' => 'Must be removed',
            'classification' => 'RM',
            'status' => 'active',
        ]);
        $vendor = Vendor::create([
            'vendor_name' => 'Vendor Preserved',
            'vendor_code' => 'V-PRESERVED',
            'status' => 'active',
        ]);

        $importer = new \App\Services\MasterDataWorkbookImporter();
        $importer->import($this->makeRealFormatWorkbook(), replaceParts: true);

        $this->assertDatabaseMissing('gci_parts', ['part_no' => 'OBSOLETE-PART']);
        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'vendor_name' => 'Vendor Preserved']);
        $this->assertDatabaseHas('gci_parts', ['part_no' => 'FG-100', 'classification' => 'FG']);
        $this->assertDatabaseHas('gci_parts', ['part_no' => 'FG-100-WIP1', 'classification' => 'WIP']);
        $this->assertDatabaseHas('gci_parts', ['part_no' => 'RM-GENERIC', 'classification' => 'RM']);
    }

    public function test_import_creates_all_entities_with_normalized_uom(): void
    {
        $importer = new \App\Services\MasterDataWorkbookImporter();
        $result = $importer->import($this->makeWorkbook());

        // FG + generic RM + WIP + substitutes
        $this->assertSame(2, GciPart::where('classification', 'FG')->count());
        $this->assertSame(2, GciPart::where('classification', 'RM')->whereDoesntHave('vendorParts')->count());
        $this->assertSame(3, GciPart::where('classification', 'RM')->whereHas('vendorParts')->count());
        // WIP-300 (dari kolom WIP Part & Parent BOM) harus terklasifikasi WIP.
        // DB test juga berisi seed bawaan lain berklasifikasi WIP — cukup cek milik importer.
        $this->assertSame('WIP', GciPart::where('part_no', 'WIP-300')->value('classification'));

        // PCS normalized to PCE
        $this->assertSame('PCE', GciPart::where('part_no', 'FG-001')->value('uom'));
        $this->assertSame('PCE', BomItem::where('line_no', 10)->where('usage_qty', 2)->value('consumption_uom'));

        // Vendor aliases collapsed: PT. One Steel == PT ONE-Steel, plus CV. Jaya
        $this->assertSame(2, Vendor::count());
        $this->assertDatabaseHas('vendors', ['vendor_name' => 'PT. One Steel']);
        $this->assertDatabaseHas('vendors', ['vendor_name' => 'CV. Jaya']);

        // Both bridge tables populated
        $this->assertSame(3, VendorPart::count());
        $this->assertSame(3, GciPartVendor::count());
        $this->assertDatabaseHas('vendor_parts', [
            'vendor_part_no' => 'SUP-111',
            'status' => 'active',
        ]);

        // Machines deterministic codes
        $this->assertSame(2, Machine::count());
        $this->assertDatabaseHas('machines', ['code' => 'MC-PRESS-01', 'name' => 'Press 01']);
        $this->assertDatabaseHas('machines', ['code' => 'MC-ASM-01', 'name' => 'Asm 01']);

        // BOM graph rebuilt: 3 parents (FG-001, WIP-300, FG-002), 4 lines
        $this->assertSame(3, Bom::count());
        $this->assertSame(4, BomItem::count());
        $this->assertDatabaseHas('bom_items', ['line_no' => 10, 'usage_qty' => 2, 'make_or_buy' => 'MAKE']);

        // WIP linkage
        $wipId = GciPart::where('part_no', 'WIP-300')->value('id');
        $this->assertDatabaseHas('bom_items', ['wip_part_id' => $wipId]);

        // Every generic material BOM line linked to all supplier substitutes
        $line = BomItem::where('line_no', 10)->where('usage_qty', 2)->first();
        $this->assertNotNull($line->incoming_part_id);
        $this->assertSame('SUP-111', VendorPart::find($line->incoming_part_id)->vendor_part_no);
        $this->assertSame(2, $line->substitutes()->count());
        $this->assertSame(
            ['SUP-111', 'SUP-112'],
            $line->substitutes()->orderBy('priority')->get()->pluck('substitute_part_no')->all()
        );

        $this->assertSame([], $result['warnings']);
    }

    public function test_import_is_deterministic_when_run_twice(): void
    {
        $importer = new \App\Services\MasterDataWorkbookImporter();
        $importer->import($this->makeWorkbook());
        $first = [
            'gci_parts' => GciPart::count(),
            'vendor_parts' => VendorPart::count(),
            'boms' => Bom::count(),
            'bom_items' => BomItem::count(),
            'substitutes' => BomItemSubstitute::count(),
            'machines' => Machine::count(),
        ];

        $importer->import($this->workbookPath);
        $second = [
            'gci_parts' => GciPart::count(),
            'vendor_parts' => VendorPart::count(),
            'boms' => Bom::count(),
            'bom_items' => BomItem::count(),
            'substitutes' => BomItemSubstitute::count(),
            'machines' => Machine::count(),
        ];

        $this->assertSame($first, $second);
    }

    public function test_dry_run_leaves_database_unchanged(): void
    {
        $importer = new \App\Services\MasterDataWorkbookImporter();

        $before = [
            'gci_parts' => GciPart::count(),
            'vendors' => Vendor::count(),
            'vendor_parts' => VendorPart::count(),
            'gci_part_vendor' => GciPartVendor::count(),
            'boms' => Bom::count(),
            'bom_items' => BomItem::count(),
            'machines' => Machine::count(),
        ];

        $result = $importer->import($this->makeWorkbook(), dryRun: true);

        $after = [
            'gci_parts' => GciPart::count(),
            'vendors' => Vendor::count(),
            'vendor_parts' => VendorPart::count(),
            'gci_part_vendor' => GciPartVendor::count(),
            'boms' => Bom::count(),
            'bom_items' => BomItem::count(),
            'machines' => Machine::count(),
        ];

        $this->assertSame($before, $after);
        $this->assertSame(2, $result['fg_parts']);
        $this->assertSame(2, $result['vendors_created']);
    }

    public function test_missing_sheet_fails(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('master FG');
        $sheet->fromArray(['Part No', 'Part Name', 'UOM'], null, 'A5');
        $sheet->fromArray([['FG-001', 'X', 'PCS']], null, 'A6');
        $path = tempnam(sys_get_temp_dir(), 'mdwb_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->workbookPath = $path;

        $this->expectException(\RuntimeException::class);
        (new \App\Services\MasterDataWorkbookImporter())->import($path);
    }

    public function test_missing_workbook_file_fails(): void
    {
        $this->expectException(\RuntimeException::class);
        (new \App\Services\MasterDataWorkbookImporter())->import('Z:/definitely/not/here.xlsx');
    }

    // ---------------------------------------------------------
    // Task 2: Artisan command
    // ---------------------------------------------------------

    public function test_command_fails_for_missing_file(): void
    {
        $this->artisan('master-data:import', ['path' => 'Z:/definitely/not/here.xlsx'])
            ->assertExitCode(1);
    }

    public function test_command_dry_run_reports_counts_without_writes(): void
    {
        $before = [
            'gci_parts' => GciPart::count(),
            'vendor_parts' => VendorPart::count(),
            'bom_items' => BomItem::count(),
        ];

        $this->artisan('master-data:import', [
            'path' => $this->makeWorkbook(),
            '--dry-run' => true,
        ])->assertExitCode(0);

        $after = [
            'gci_parts' => GciPart::count(),
            'vendor_parts' => VendorPart::count(),
            'bom_items' => BomItem::count(),
        ];

        $this->assertSame($before, $after);
    }

    public function test_command_real_run_commits_data(): void
    {
        $this->artisan('master-data:import', ['path' => $this->makeWorkbook()])
            ->assertExitCode(0);

        $this->assertSame(2, GciPart::where('classification', 'FG')->count());
        $this->assertSame(4, BomItem::count());
    }

    public function test_command_replace_parts_rebuilds_part_master_from_workbook(): void
    {
        GciPart::create([
            'part_no' => 'OBSOLETE-PART',
            'part_name' => 'Must be removed',
            'classification' => 'RM',
            'status' => 'active',
        ]);

        $this->artisan('master-data:import', [
            'path' => $this->makeRealFormatWorkbook(),
            '--replace-parts' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('gci_parts', ['part_no' => 'OBSOLETE-PART']);
        $this->assertDatabaseHas('gci_parts', ['part_no' => 'FG-100-WIP1', 'classification' => 'WIP']);
    }
}
