<?php

namespace Tests\Feature;

use App\Imports\ForecastPlanImport;
use App\Models\GciPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastPlanImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_parses_month_columns_and_resolves_gci_part(): void
    {
        $gci = GciPart::create([
            'part_no' => 'IJPGIRFOMGNS',
            'part_name' => 'Sample FG',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $import = new ForecastPlanImport();

        $rows = collect([
            collect(['part_code', '2026-09', '2026-10', '2026-11']),
            collect(['IJPGIRFOMGNS', 5000, 6000, 4500]),
            collect(['UNKNOWN-999', 100, 200, 300]),
        ]);

        $import->collection($rows);

        $this->assertSame(['2026-09', '2026-10', '2026-11'], $import->periods);
        $this->assertCount(2, $import->rows);
        $this->assertCount(1, $import->unmapped);

        $mapped = $import->rows[0];
        $this->assertSame('IJPGIRFOMGNS', $mapped['customer_part_no']);
        $this->assertSame($gci->id, $mapped['gci_part_id']);
        $this->assertSame('mapped', $mapped['mapping_status']);
        $this->assertSame(5000.0, $mapped['quantities']['2026-09']);
        $this->assertSame(6000.0, $mapped['quantities']['2026-10']);
        $this->assertSame(4500.0, $mapped['quantities']['2026-11']);

        // Unknown row is unmapped
        $unmapped = $import->rows[1];
        $this->assertSame('UNKNOWN-999', $unmapped['customer_part_no']);
        $this->assertNull($unmapped['gci_part_id']);
        $this->assertSame('unmapped', $unmapped['mapping_status']);
    }

    public function test_import_handles_headers_with_dashes_becoming_spaces(): void
    {
        // simulate normalized headers where "2026-09" became "2026 09"
        $import = new ForecastPlanImport();

        $rows = collect([
            collect(['Part No.', '2026 09', '2026 10']),
            collect(['IJPGIRFOMGNS', 100, 200]),
        ]);

        $import->collection($rows);

        $this->assertSame(['2026-09', '2026-10'], $import->periods);
        $this->assertCount(1, $import->rows);
        $this->assertSame(100.0, $import->rows[0]['quantities']['2026-09']);
    }

    public function test_import_reports_failure_when_no_period_columns(): void
    {
        $import = new ForecastPlanImport();

        $rows = collect([
            collect(['part_code', 'qty', 'notes']),
            collect(['IJPGIRFOMGNS', 100, 'x']),
        ]);

        $import->collection($rows);

        $this->assertNotEmpty($import->failures);
        $this->assertEmpty($import->rows);
    }
}
