<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use App\Models\MrpProductionPlan;
use App\Models\MrpRun;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionWorkOrderGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private GciPart $fg;
    private GciPart $rm;
    private BomItem $bomItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->fg = GciPart::create([
            'part_no' => 'FG-001',
            'part_name' => 'FG TEST 001',
            'classification' => 'FG',
            'status' => 'active',
        ]);
        $this->rm = GciPart::create([
            'part_no' => 'RM-001',
            'part_name' => 'RM TEST 001',
            'classification' => 'RM',
            'status' => 'active',
        ]);

        $bom = Bom::create([
            'part_id' => $this->fg->id,
            'revision' => 'A',
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->bomItem = BomItem::create([
            'bom_id' => $bom->id,
            'component_part_id' => $this->rm->id,
            'component_part_no' => $this->rm->part_no,
            'line_no' => 1,
            'usage_qty' => 2,
            'scrap_factor' => 0.1,
            'yield_factor' => 1,
            'consumption_uom' => 'PCS',
            'process_name' => 'FORMING',
        ]);
    }

    public function test_generate_manual_work_order_creates_snapshots_and_history(): void
    {
        $resp = $this->post(route('production.work-orders.generate'), [
            'source_type' => 'manual',
            'fg_part_id' => $this->fg->id,
            'qty_plan' => 10,
            'plan_date' => now()->toDateString(),
            'priority' => 2,
            'remarks' => 'manual test',
        ]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('work_orders', [
            'source_type' => 'manual',
            'fg_part_id' => $this->fg->id,
            'status' => 'open',
        ]);

        $wo = WorkOrder::query()->firstOrFail();
        $this->assertDatabaseHas('work_order_bom_snapshots', [
            'work_order_id' => $wo->id,
            'bom_item_id' => $this->bomItem->id,
        ]);
        $this->assertDatabaseHas('work_order_requirement_snapshots', [
            'work_order_id' => $wo->id,
            'component_part_id' => $this->rm->id,
        ]);
        $this->assertDatabaseHas('work_order_histories', [
            'work_order_id' => $wo->id,
            'event_type' => 'created',
        ]);
    }

    public function test_generate_from_mrp_autofill_and_freeze_snapshot(): void
    {
        $mrpRun = MrpRun::create([
            'period' => now()->format('Y-m'),
            'status' => 'completed',
            'run_by' => $this->user->id,
            'run_at' => now(),
        ]);

        $mrp = MrpProductionPlan::create([
            'mrp_run_id' => $mrpRun->id,
            'part_id' => $this->fg->id,
            'plan_date' => now()->toDateString(),
            'net_required' => 20,
            'planned_order_rec' => 20,
            'planned_qty' => 20,
        ]);

        $this->post(route('production.work-orders.generate'), [
            'source_type' => 'mrp',
            'source_ref_id' => $mrp->id,
            'priority' => 3,
        ])->assertRedirect();

        $wo = WorkOrder::query()->firstOrFail();
        $this->assertEquals('mrp', $wo->source_type);
        $this->assertEquals($mrp->id, $wo->source_ref_id);
        $this->assertEquals(20.0, (float) $wo->qty_plan);

        $snapshotBefore = (float) $wo->bomSnapshots()->firstOrFail()->net_required_per_fg;
        $this->bomItem->update(['usage_qty' => 99]);
        $snapshotAfter = (float) $wo->fresh()->bomSnapshots()->firstOrFail()->net_required_per_fg;
        $this->assertSame($snapshotBefore, $snapshotAfter);
    }

    public function test_generate_from_outgoing_daily_autofill_is_valid(): void
    {
        $plan = OutgoingDailyPlan::create([
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $row = OutgoingDailyPlanRow::create([
            'plan_id' => $plan->id,
            'row_no' => 1,
            'production_line' => 'LINE-1',
            'part_no' => $this->fg->part_no,
            'gci_part_id' => $this->fg->id,
        ]);

        $cell = OutgoingDailyPlanCell::create([
            'row_id' => $row->id,
            'plan_date' => now()->toDateString(),
            'seq' => 1,
            'qty' => 45,
        ]);

        $this->post(route('production.work-orders.generate'), [
            'source_type' => 'outgoing_daily',
            'source_ref_id' => $cell->id,
            'priority' => 1,
        ])->assertRedirect();

        $wo = WorkOrder::query()->firstOrFail();
        $this->assertEquals('outgoing_daily', $wo->source_type);
        $this->assertEquals($cell->id, $wo->source_ref_id);
        $this->assertEquals(45.0, (float) $wo->qty_plan);
    }
}

