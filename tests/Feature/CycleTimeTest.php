<?php

namespace Tests\Feature;

use App\Models\GciPart;
use App\Models\Machine;
use App\Models\ProductionDowntime;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleTimeTest extends TestCase
{
    use RefreshDatabase;

    private GciPart $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->part = GciPart::create([
            'part_no' => 'FG-CT-001',
            'part_name' => 'Casing A',
            'classification' => 'FG',
            'status' => 'active',
        ]);
    }

    private function makeMachine(array $overrides = []): Machine
    {
        static $seq = 0;
        $seq++;

        return Machine::create(array_merge([
            'code' => 'MC-'.$seq,
            'name' => 'Mesin '.$seq,
            'cycle_time' => 30,
            'cycle_time_unit' => 'second',
            'is_active' => true,
        ], $overrides));
    }

    private function makeOrder(Machine $machine, array $overrides = []): ProductionOrder
    {
        static $seq = 0;
        $seq++;

        return ProductionOrder::create(array_merge([
            'production_order_number' => 'PO-CT-'.$seq,
            'gci_part_id' => $this->part->id,
            'machine_id' => $machine->id,
            'plan_date' => '2026-09-05',
            'qty_planned' => 100,
            'qty_actual' => 100,
            'status' => 'completed',
            'start_time' => '2026-09-05 08:00:00',
            'end_time' => '2026-09-05 13:00:00', // 300 min span
        ], $overrides));
    }

    private function url(string $query = ''): string
    {
        return '/production/cycle-time?from=2026-09-01&to=2026-09-30'.$query;
    }

    public function test_user_without_view_production_is_forbidden(): void
    {
        // 'purchasing' role has no view_production
        $this->actingAs(User::factory()->create(['role' => 'purchasing']))
            ->get($this->url())
            ->assertForbidden();
    }

    public function test_viewer_role_can_open_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get($this->url())
            ->assertOk();
    }

    public function test_flags_machines_below_performance_threshold(): void
    {
        $this->actingAs(User::factory()->create());

        // Slow: std 30s x 100 pc = 50 min vs actual 300 min span -> 16.7%
        $slow = $this->makeMachine(['name' => 'Mesin Slow', 'cycle_time' => 30]);
        $this->makeOrder($slow);

        // Good: std 120s x 100 pc = 200 min; span 240 min - 40 downtime = 200 -> 100%
        $good = $this->makeMachine(['name' => 'Mesin Good', 'cycle_time' => 120]);
        $goodOrder = $this->makeOrder($good, [
            'start_time' => '2026-09-05 08:00:00',
            'end_time' => '2026-09-05 12:00:00',
        ]);
        ProductionDowntime::create([
            'production_order_id' => $goodOrder->id,
            'start_time' => '09:00',
            'end_time' => '09:40',
            'duration_minutes' => 40,
            'category' => 'setting',
        ]);

        $response = $this->get($this->url())->assertOk();
        $rows = $response->viewData('rows');

        $this->assertCount(2, $rows);
        // Worst performance is sorted first
        $this->assertSame('Mesin Slow', $rows[0]->machine_name);
        $this->assertTrue($rows[0]->warning);
        $this->assertEqualsWithDelta(16.7, $rows[0]->performance, 0.1);
        $this->assertEqualsWithDelta(180.0, $rows[0]->actual_sec_per_pc, 0.1);

        $this->assertFalse($rows[1]->warning);
        $this->assertEqualsWithDelta(100.0, $rows[1]->performance, 0.1);
        // Actual cycle must exclude downtime: 200 min / 100 pc = 120s
        $this->assertEqualsWithDelta(120.0, $rows[1]->actual_sec_per_pc, 0.1);
    }

    public function test_excludes_cancelled_and_out_of_period_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $machine = $this->makeMachine(['name' => 'Mesin Solo']);
        $this->makeOrder($machine);
        $this->makeOrder($machine, ['status' => 'cancelled', 'plan_date' => '2026-09-06']);
        $this->makeOrder($machine, ['plan_date' => '2020-01-01']);

        $response = $this->get($this->url())->assertOk();
        $rows = $response->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]->orders);
    }

    public function test_machine_filter_narrows_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $a = $this->makeMachine(['name' => 'Alpha']);
        $b = $this->makeMachine(['name' => 'Beta']);
        $this->makeOrder($a);
        $this->makeOrder($b);

        $response = $this->get($this->url('&machine_id='.$a->id))->assertOk();
        $rows = $response->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertSame('Alpha', $rows[0]->machine_name);
    }

    public function test_empty_period_returns_no_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $machine = $this->makeMachine();
        $this->makeOrder($machine, ['plan_date' => '2020-05-05']);

        $response = $this->get('/production/cycle-time?from=2026-09-01&to=2026-09-30')->assertOk();
        $this->assertCount(0, $response->viewData('rows'));
    }
}
