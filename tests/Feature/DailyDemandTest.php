<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDemandTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(int $userId, array $overrides = []): OutgoingDailyPlan
    {
        return OutgoingDailyPlan::create(array_merge([
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-05',
            'created_by' => $userId,
        ], $overrides));
    }

    private function makeDemand(Customer $customer, string $partNo, array $qtyByDate, array $rowOverrides = []): void
    {
        $plan = OutgoingDailyPlan::latest('id')->firstOrFail();
        $customerPart = CustomerPart::create([
            'customer_id' => $customer->id,
            'customer_part_no' => $partNo,
            'customer_part_name' => 'Part '.$partNo,
            'status' => 'active',
        ]);

        $row = OutgoingDailyPlanRow::create(array_merge([
            'plan_id' => $plan->id,
            'row_no' => 1,
            'production_line' => 'LINE-A',
            'part_no' => $partNo,
            'customer_part_id' => $customerPart->id,
        ], $rowOverrides));

        $seq = 1;
        foreach ($qtyByDate as $date => $qty) {
            OutgoingDailyPlanCell::create([
                'row_id' => $row->id,
                'plan_date' => $date,
                'seq' => $seq++,
                'qty' => $qty,
            ]);
        }
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('planning.daily-demand.index'))->assertRedirect(route('login', absolute: false));
    }

    public function test_staff_can_view_the_report(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $this->get(route('planning.daily-demand.index'))
            ->assertOk()
            ->assertSee(trans('daily_demand.title'));
    }

    public function test_roles_without_view_planning_are_forbidden(): void
    {
        // 'purchasing' role has no view_planning
        $this->actingAs(User::factory()->create(['role' => 'purchasing']));
        $this->get(route('planning.daily-demand.index'))->assertForbidden();
    }

    public function test_demand_is_aggregated_per_customer_per_day(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $customer = Customer::create(['code' => 'C1', 'name' => 'Acme Motors', 'status' => 'active']);
        $plan = $this->makePlan($user->id);
        $this->makeDemand($customer, 'P-100', [
            '2026-09-01' => 50,
            '2026-09-02' => 30,
        ]);

        $response = $this->get(route('planning.daily-demand.index'));
        $response->assertOk()->assertSee('Acme Motors');

        $customers = $response->viewData('customers');
        $this->assertCount(1, $customers);
        $this->assertSame(80.0, $customers[0]['total']);
        $this->assertSame(50.0, $customers[0]['qty_by_day']['2026-09-01']);
        $this->assertSame(30.0, $customers[0]['qty_by_day']['2026-09-02']);
        $this->assertSame(80.0, $response->viewData('grandTotal'));
    }

    public function test_twin_rows_are_merged_and_cells_summed_once_per_plan(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $customer = Customer::create(['code' => 'C2', 'name' => 'Beta Corp', 'status' => 'active']);
        $plan = $this->makePlan($user->id);

        $customerPart = CustomerPart::create([
            'customer_id' => $customer->id,
            'customer_part_no' => 'P-200',
            'status' => 'active',
        ]);

        // Master + twin component rows for the same demand line.
        $master = OutgoingDailyPlanRow::create([
            'plan_id' => $plan->id,
            'row_no' => 1,
            'production_line' => 'LINE-B',
            'part_no' => 'P-200',
            'customer_part_id' => $customerPart->id,
        ]);
        $twin = OutgoingDailyPlanRow::create([
            'plan_id' => $plan->id,
            'row_no' => 2,
            'production_line' => 'LINE-B',
            'part_no' => 'P-200',
            'customer_part_id' => $customerPart->id,
        ]);
        OutgoingDailyPlanCell::create(['row_id' => $master->id, 'plan_date' => '2026-09-01', 'seq' => 1, 'qty' => 40]);
        OutgoingDailyPlanCell::create(['row_id' => $twin->id, 'plan_date' => '2026-09-01', 'seq' => 2, 'qty' => 10]);

        $response = $this->get(route('planning.daily-demand.index'));
        $customers = $response->viewData('customers');

        $this->assertCount(1, $customers);
        $this->assertCount(1, $customers[0]['parts'], 'Twin rows must merge into one part line');
        $this->assertSame(50.0, $customers[0]['total']);
        $this->assertSame(50.0, $customers[0]['qty_by_day']['2026-09-01']);
    }

    public function test_rows_without_customer_map_to_unmapped_bucket(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $plan = $this->makePlan($user->id);
        $row = OutgoingDailyPlanRow::create([
            'plan_id' => $plan->id,
            'row_no' => 1,
            'production_line' => 'LINE-C',
            'part_no' => 'P-300',
            'customer_part_id' => null,
        ]);
        OutgoingDailyPlanCell::create(['row_id' => $row->id, 'plan_date' => '2026-09-03', 'seq' => 1, 'qty' => 25]);

        $response = $this->get(route('planning.daily-demand.index'));
        $customers = $response->viewData('customers');

        $this->assertCount(1, $customers);
        $this->assertSame(trans('daily_demand.unmapped'), $customers[0]['name']);
        $this->assertSame(25.0, $customers[0]['total']);
    }

    public function test_zero_qty_lines_are_hidden_and_empty_plan_shows_hint(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $customer = Customer::create(['code' => 'C3', 'name' => 'Gamma Ltd', 'status' => 'active']);
        $plan = $this->makePlan($user->id);
        $this->makeDemand($customer, 'P-400', ['2026-09-01' => 0]);

        $response = $this->get(route('planning.daily-demand.index'));
        $this->assertCount(0, $response->viewData('customers'));

        // A plan with no rows at all
        $plan2 = $this->makePlan($user->id, ['date_from' => '2026-10-01', 'date_to' => '2026-10-02']);
        $response2 = $this->get(route('planning.daily-demand.index', ['plan_id' => $plan2->id]));
        $response2->assertOk()->assertSee(trans('daily_demand.no_data'));
    }
}
