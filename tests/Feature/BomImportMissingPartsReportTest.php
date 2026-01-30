<?php

namespace Tests\Feature;

use App\Imports\BomImport;
use App\Models\GciPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomImportMissingPartsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_import_collects_missing_parts_without_crashing(): void
    {
        GciPart::create([
            'part_no' => 'FG-001',
            'part_name' => 'FG TEST',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $import = new BomImport();

        // Missing component RM should be recorded but row can still be created (string-only).
        $import->model([
            'no' => 1,
            'fg_part_no' => 'FG-001',
            'rm_part_no' => 'RM-NOT-EXIST',
            'consumption' => 1,
            'make_or_buy' => 'buy',
        ]);

        $this->assertArrayHasKey('RM-NOT-EXIST', $import->missingComponentParts);

        // Missing FG should be recorded and row skipped (no exception thrown).
        $import->model([
            'no' => 1,
            'fg_part_no' => 'FG-NOT-EXIST',
            'rm_part_no' => 'RM-001',
            'consumption' => 1,
            'make_or_buy' => 'buy',
        ]);

        $this->assertArrayHasKey('FG-NOT-EXIST', $import->missingFgParts);
    }
}

