<?php

namespace Tests\Feature;

use App\Models\StockOpnameItem;
use App\Models\StockOpnameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for QA input-scan finding (blind-count barcode data-loss).
 *
 * StockOpnameItem::$fillable omitted 'barcode_raw', so the mobile/blind-count
 * scanner's updateOrCreate([... 'barcode_raw' => ...]) silently dropped the
 * scanned barcode of unknown parts. StockOpnameItem::create/update would never
 * persist barcode_raw even though the column exists.
 *
 * Found by /qa input-scan on 2026-08-25.
 */
class StockOpnameFillableRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_barcode_raw(): void
    {
        $user = User::factory()->create();

        $session = StockOpnameSession::create([
            'session_no' => StockOpnameSession::generateSessionNo(),
            'name' => 'Regression Blind Count',
            'status' => 'OPEN',
            'created_by' => $user->id,
        ]);

        $item = StockOpnameItem::create([
            'session_id' => $session->id,
            'location_code' => 'WH-A',
            'gci_part_id' => null, // blind record: unknown part
            'barcode_raw' => 'BARCODE-REG-001',
            'batch' => null,
            'system_qty' => 0,
            'counted_qty' => 1,
            'counted_by' => $user->id,
            'counted_at' => now(),
            'notes' => 'blind scan',
        ]);

        $fresh = StockOpnameItem::find($item->id);

        $this->assertSame('BARCODE-REG-001', $fresh->barcode_raw);
    }

    public function test_barcode_raw_is_fillable(): void
    {
        $this->assertTrue((new StockOpnameItem)->isFillable('barcode_raw'));
        // sanity: sibling columns still fillable
        $this->assertTrue((new StockOpnameItem)->isFillable('batch'));
        $this->assertTrue((new StockOpnameItem)->isFillable('location_code'));
    }
}
