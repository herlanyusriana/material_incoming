<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GciPart;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use App\Models\StockAtCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryRequirementsStockAtCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_requirements_qty_is_reduced_by_stock_at_customer(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'code' => 'CUST-1',
            'name' => 'Customer 1',
            'status' => 'active',
        ]);

        $fg = GciPart::create([
            'customer_id' => $customer->id,
            'part_no' => 'FG-001',
            'part_name' => 'FG 001',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $date = now()->startOfDay();

        $plan = OutgoingDailyPlan::create([
            'date_from' => $date->toDateString(),
            'date_to' => $date->toDateString(),
            'created_by' => $user->id,
        ]);

        $row = OutgoingDailyPlanRow::create([
            'plan_id' => $plan->id,
            'row_no' => 1,
            'production_line' => 'LINE-1',
            'part_no' => $fg->part_no,
            'gci_part_id' => $fg->id,
        ]);

        OutgoingDailyPlanCell::create([
            'row_id' => $row->id,
            'plan_date' => $date->toDateString(),
            'seq' => 1,
            'qty' => 100,
        ]);

        $payload = [
            'period' => $date->format('Y-m'),
            'customer_id' => $customer->id,
            'gci_part_id' => $fg->id,
            'part_no' => $fg->part_no,
        ];
        $payload['day_' . (int) $date->format('j')] = 30;
        StockAtCustomer::create($payload);

        $resp = $this->actingAs($user)->get(route('outgoing.delivery-requirements', [
            'date_from' => $date->toDateString(),
            'date_to' => $date->toDateString(),
        ]));

        $resp->assertOk();
        $requirements = collect($resp->viewData('requirements'));
        $this->assertNotEmpty($requirements->all());

        $req = $requirements->first(fn ($r) => (string) ($r->gci_part?->part_no ?? '') === 'FG-001' || (string) ($r->customer_part_no ?? '') === 'FG-001');
        $this->assertNotNull($req);
        $this->assertSame(100.0, (float) ($req->gross_qty ?? 0));
        $this->assertSame(30.0, (float) ($req->stock_at_customer ?? 0));
        $this->assertSame(30.0, (float) ($req->stock_used ?? 0));
        $this->assertSame(70.0, (float) ($req->total_qty ?? 0));
    }
}
