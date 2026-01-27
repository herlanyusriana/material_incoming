<?php

namespace Tests\Feature;

use App\Imports\OutgoingDailyPlanningImport;
use Tests\TestCase;

class OutgoingDailyPlanningImportTest extends TestCase
{
    public function test_import_parses_date_columns_from_template_headers(): void
    {
        $import = new OutgoingDailyPlanningImport();

        $rows = collect([
            collect(['No', 'LINE', 'PART NO', '2026-01-30 Seq', '2026-01-30 Qty']),
            collect([1, 'NR1', '', 1, 10]),
        ]);

        $import->collection($rows);

        $this->assertNotNull($import->dateFrom());
        $this->assertNotNull($import->dateTo());
        $this->assertSame('2026-01-30', $import->dateFrom()->toDateString());
        $this->assertSame('2026-01-30', $import->dateTo()->toDateString());
        $this->assertCount(1, $import->rows);
    }

    public function test_import_parses_date_columns_even_if_dashes_become_spaces(): void
    {
        $import = new OutgoingDailyPlanningImport();

        $rows = collect([
            collect(['No', 'LINE', 'Part No.', '2026 01 30 Seq', '2026 01 30 Qty']),
            collect([1, 'NR2', '', 2, 20]),
        ]);

        $import->collection($rows);

        $this->assertNotNull($import->dateFrom());
        $this->assertSame('2026-01-30', $import->dateFrom()->toDateString());
    }
}

