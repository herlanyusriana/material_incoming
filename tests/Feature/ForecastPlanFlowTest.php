<?php

namespace Tests\Feature;

use App\Models\ForecastDocument;
use App\Models\GciPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ForecastPlanFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'username' => 'planner_' . uniqid(),
        ]);
    }

    protected function gciPart(): GciPart
    {
        return GciPart::create([
            'part_no' => 'IJPGIRFOMGNS',
            'part_name' => 'Sample FG',
            'classification' => 'FG',
            'status' => 'active',
        ]);
    }

    protected function makePlanUpload(): UploadedFile
    {
        $sp = new Spreadsheet();
        $ws = $sp->getActiveSheet();
        $ws->setCellValue('A1', 'part_code');
        $ws->setCellValue('B1', '2026-09');
        $ws->setCellValue('C1', '2026-10');
        $ws->setCellValue('D1', '2026-11');

        $ws->setCellValue('A2', 'IJPGIRFOMGNS');
        $ws->setCellValue('B2', 5000);
        $ws->setCellValue('C2', 6000);
        $ws->setCellValue('D2', 4500);

        $ws->setCellValue('A3', 'UNKNOWN-999');
        $ws->setCellValue('B3', 100);
        $ws->setCellValue('C3', 200);
        $ws->setCellValue('D3', 300);

        $tmp = tempnam(sys_get_temp_dir(), 'plan');
        $path = $tmp . '.xlsx';
        $writer = new Xlsx($sp);
        $writer->save($path);

        return new UploadedFile($path, 'plan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_preview_creates_document_and_shows_rows(): void
    {
        $gci = $this->gciPart();
        $user = $this->user();

        $response = $this->actingAs($user)->post(route('planning.forecasts.preview-plan'), [
            'file' => $this->makePlanUpload(),
        ]);

        $response->assertOk();
        $response->assertViewHas('document');

        $document = ForecastDocument::where('source', 'lG_plan')->first();
        $this->assertNotNull($document);
        $this->assertSame(2, $document->total_rows);
        $this->assertSame(1, $document->mapped_rows);
        $this->assertSame(1, $document->unmapped_rows);
        $this->assertSame('preview', $document->status);
        $this->assertSame('2026-09', $document->period_start);
        $this->assertSame('2026-11', $document->period_end);
        $this->assertSame(['2026-09', '2026-10', '2026-11'], array_keys($document->rows->first()->quantities));
    }

    public function test_confirm_commits_mapped_rows_to_forecast(): void
    {
        $this->gciPart();
        $user = $this->user();

        // Create a preview document + rows directly, then confirm via HTTP.
        $doc = ForecastDocument::create([
            'document_no' => 'PLAN-TEST',
            'source' => 'lG_plan',
            'period_start' => '2026-09',
            'period_end' => '2026-11',
            'uploaded_by' => $user->id,
            'uploaded_at' => now(),
            'status' => 'preview',
            'total_rows' => 1,
            'mapped_rows' => 1,
            'unmapped_rows' => 0,
        ]);

        $gci = GciPart::where('part_no', 'IJPGIRFOMGNS')->first();
        $doc->rows()->create([
            'customer_part_no' => 'IJPGIRFOMGNS',
            'gci_part_id' => $gci->id,
            'mapping_status' => 'mapped',
            'row_no' => '2',
            'quantities' => ['2026-09' => 5000, '2026-10' => 6000, '2026-11' => 4500],
        ]);

        $response = $this->actingAs($user)->post(route('planning.forecasts.confirm-plan', $doc));

        $response->assertRedirect(route('planning.forecasts.index'));

        $this->assertSame('committed', $doc->fresh()->status);

        $this->assertDatabaseHas('forecasts', [
            'part_id' => $gci->id,
            'period' => '2026-09',
            'planning_qty' => 5000,
            'qty' => 5000,
            'source' => 'lG_plan',
        ]);
        $this->assertDatabaseHas('forecasts', [
            'part_id' => $gci->id,
            'period' => '2026-11',
            'planning_qty' => 4500,
            'qty' => 4500,
        ]);
    }
}
