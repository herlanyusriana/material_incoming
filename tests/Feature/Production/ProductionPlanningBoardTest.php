<?php

namespace Tests\Feature\Production;

use App\Models\NewSchema\Core\GciPart;
use App\Models\ProductionOrder;
use App\Models\ProductionPlanningLine;
use App\Models\ProductionPlanningSession;
use App\Models\User;
use App\Services\ProductionPlanningBoardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionPlanningBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_quantities_are_stored_in_sessions_for_their_actual_calendar_dates(): void
    {
        $part = GciPart::query()->create([
            'part_no' => 'FG-DAY-001',
            'part_name' => 'Calendar Day Finished Good',
            'classification' => 'FG',
            'status' => 'active',
            'uom' => 'PCS',
        ]);

        $service = app(ProductionPlanningBoardService::class);

        $service->setDailyQuantity($part->id, '2026-09-15', 120, null);
        $service->setDailyQuantity($part->id, '2026-09-16', 80, null);

        $this->assertDatabaseHas('production_planning_sessions', ['plan_date' => '2026-09-15']);
        $this->assertDatabaseHas('production_planning_sessions', ['plan_date' => '2026-09-16']);

        $day = ProductionPlanningSession::query()->whereDate('plan_date', '2026-09-15')->firstOrFail();
        $nextDay = ProductionPlanningSession::query()->whereDate('plan_date', '2026-09-16')->firstOrFail();

        $this->assertDatabaseHas('production_planning_lines', [
            'session_id' => $day->id,
            'gci_part_id' => $part->id,
            'plan_qty' => 120,
        ]);
        $this->assertDatabaseHas('production_planning_lines', [
            'session_id' => $nextDay->id,
            'gci_part_id' => $part->id,
            'plan_qty' => 80,
        ]);

        $rows = $service->rows('2026-09-15', 3);

        $this->assertCount(1, $rows);
        $this->assertSame([120.0, 80.0, 0.0], array_values($rows->first()['daily_quantities']));
        $this->assertSame(
            ['2026-09-15', '2026-09-16', '2026-09-17'],
            array_keys($rows->first()['daily_quantities'])
        );
    }

    public function test_moving_a_part_updates_its_priority_across_the_visible_day_window(): void
    {
        $first = $this->createFinishedGood('FG-ORDER-001');
        $second = $this->createFinishedGood('FG-ORDER-002');
        $service = app(ProductionPlanningBoardService::class);

        foreach (['2026-09-15', '2026-09-16'] as $date) {
            $service->setDailyQuantity($first->id, $date, 100, null);
            $service->setDailyQuantity($second->id, $date, 200, null);
        }

        $service->movePart($second->id, '2026-09-15', 3, 'up');

        $this->assertSame(
            ['FG-ORDER-002', 'FG-ORDER-001'],
            $service->rows('2026-09-15', 3)->pluck('part.part_no')->all()
        );

        foreach (['2026-09-15', '2026-09-16'] as $date) {
            $session = ProductionPlanningSession::query()->whereDate('plan_date', $date)->firstOrFail();
            $this->assertDatabaseHas('production_planning_lines', [
                'session_id' => $session->id,
                'gci_part_id' => $second->id,
                'sort_order' => 1,
            ]);
            $this->assertDatabaseHas('production_planning_lines', [
                'session_id' => $session->id,
                'gci_part_id' => $first->id,
                'sort_order' => 2,
            ]);
        }
    }

    public function test_removing_a_part_from_the_window_keeps_the_part_master_and_generated_wo(): void
    {
        $part = $this->createFinishedGood('FG-REMOVE-001');
        $service = app(ProductionPlanningBoardService::class);
        $line = $service->setDailyQuantity($part->id, '2026-09-15', 150, null);

        $order = ProductionOrder::query()->create([
            'production_order_number' => 'WO-KEEP-001',
            'transaction_no' => 'WO0001150926',
            'gci_part_id' => $part->id,
            'planning_line_id' => $line->id,
            'plan_date' => '2026-09-15',
            'qty_planned' => 150,
            'qty_actual' => 0,
            'status' => 'planned',
        ]);

        $removed = $service->removePart($part->id, '2026-09-15', 3);

        $this->assertSame(1, $removed);
        $this->assertDatabaseMissing('production_planning_lines', ['id' => $line->id]);
        $this->assertDatabaseHas('gci_parts', ['id' => $part->id, 'part_no' => 'FG-REMOVE-001']);
        $this->assertDatabaseHas('production_orders', ['id' => $order->id, 'production_order_number' => 'WO-KEEP-001']);
        $this->assertNull($order->fresh()->planning_line_id);
    }

    public function test_daily_quantity_endpoint_returns_the_actual_saved_date_and_quantity(): void
    {
        $part = $this->createFinishedGood('FG-HTTP-001');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->putJson('/production/planning/day-quantity', [
                'gci_part_id' => $part->id,
                'plan_date' => '2026-09-16',
                'qty' => 275,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('plan_date', '2026-09-16')
            ->assertJsonPath('qty', 275);

        $session = ProductionPlanningSession::query()->whereDate('plan_date', '2026-09-16')->firstOrFail();
        $this->assertDatabaseHas('production_planning_lines', [
            'session_id' => $session->id,
            'gci_part_id' => $part->id,
            'plan_qty' => 275,
        ]);
    }

    public function test_window_wo_generation_uses_each_daily_lines_calendar_date(): void
    {
        $part = $this->createFinishedGood('FG-WO-DAY-001');
        $user = User::factory()->create(['role' => 'admin']);
        $service = app(ProductionPlanningBoardService::class);
        $service->setDailyQuantity($part->id, '2026-09-15', 100, $user->id);
        $service->setDailyQuantity($part->id, '2026-09-16', 140, $user->id);

        $this->actingAs($user)
            ->post('/production/planning/window-wo', [
                'gci_part_id' => $part->id,
                'start_date' => '2026-09-15',
                'days' => 3,
            ])
            ->assertRedirect('/production/planning?date=2026-09-15&days=3');

        $this->assertDatabaseHas('production_orders', [
            'gci_part_id' => $part->id,
            'plan_date' => '2026-09-15',
            'qty_planned' => 100,
            'shift' => 1,
        ]);
        $this->assertDatabaseHas('production_orders', [
            'gci_part_id' => $part->id,
            'plan_date' => '2026-09-16',
            'qty_planned' => 140,
            'shift' => 1,
        ]);
        $this->assertSame(2, ProductionOrder::query()->where('gci_part_id', $part->id)->count());
    }

    public function test_planning_page_renders_calendar_day_columns_instead_of_shift_columns(): void
    {
        $part = $this->createFinishedGood('FG-BOARD-001');
        $user = User::factory()->create(['role' => 'admin']);
        app(ProductionPlanningBoardService::class)
            ->setDailyQuantity($part->id, '2026-09-15', 90, $user->id);

        $this->actingAs($user)
            ->get('/production/planning?date=2026-09-15&days=3')
            ->assertOk()
            ->assertSeeText('Production Plan')
            ->assertSeeText('Nama Mesin')
            ->assertSeeText('FG Part')
            ->assertSeeText('WIP Part')
            ->assertSeeText('D+1')
            ->assertSeeText('16 Sep 2026')
            ->assertSeeText('FG-BOARD-001')
            ->assertDontSeeText('Shift 1');
    }

    private function createFinishedGood(string $partNo): GciPart
    {
        return GciPart::query()->create([
            'part_no' => $partNo,
            'part_name' => $partNo . ' Name',
            'classification' => 'FG',
            'status' => 'active',
            'uom' => 'PCS',
        ]);
    }
}
