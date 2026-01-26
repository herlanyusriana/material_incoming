<?php

namespace Tests\Feature;

use App\Imports\BomSubstituteImport;
use App\Imports\BomSubstituteMappingImport;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ImportFailuresContractTest extends TestCase
{
    public function test_bom_substitute_import_failures_expose_row_and_errors_methods(): void
    {
        $import = new BomSubstituteImport();

        $rows = new Collection([
            new Collection([
                'fg_part_no' => '',
                'component_part_no' => '',
                'substitute_part_no' => '',
            ]),
        ]);

        $import->collection($rows);

        $failures = $import->failures();
        $this->assertNotEmpty($failures);

        $failure = $failures[0];
        $this->assertTrue(method_exists($failure, 'row'));
        $this->assertTrue(method_exists($failure, 'errors'));
        $this->assertSame(2, $failure->row());
        $this->assertIsArray($failure->errors());
    }

    public function test_bom_substitute_mapping_import_failures_expose_row_and_errors_methods(): void
    {
        $import = new BomSubstituteMappingImport();

        $rows = new Collection([
            new Collection([
                'component_part_no' => '',
                'substitute_part_no' => '',
            ]),
        ]);

        $import->collection($rows);

        $failures = $import->failures();
        $this->assertNotEmpty($failures);

        $failure = $failures[0];
        $this->assertTrue(method_exists($failure, 'row'));
        $this->assertTrue(method_exists($failure, 'errors'));
        $this->assertSame(2, $failure->row());
        $this->assertIsArray($failure->errors());
    }
}

