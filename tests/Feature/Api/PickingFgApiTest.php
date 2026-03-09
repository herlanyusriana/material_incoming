<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\GciPart;
use App\Models\OutgoingPickingFg;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PickingFgApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pick_updates_qty_normally_when_single_open_task_exists(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        [$date, $part, $do] = $this->seedBaseData();
        $pick = OutgoingPickingFg::create([
            'delivery_date' => $date,
            'gci_part_id' => $part->id,
            'delivery_order_id' => $do->id,
            'source' => 'daily_plan',
            'qty_plan' => 10,
            'qty_picked' => 2,
            'status' => 'picking',
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/picking-fg/pick', [
            'date' => $date,
            'part_no' => $part->part_no,
            'qty' => 3,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.qty_picked', 5)
            ->assertJsonPath('data.qty_remaining', 5)
            ->assertJsonPath('data.status', 'picking')
            ->assertJsonPath('data.applied_qty', 3)
            ->assertJsonPath('data.rejected_qty', 0);

        $this->assertSame(5, (int) $pick->fresh()->qty_picked);
    }

    public function test_pick_caps_to_plan_and_marks_completed_when_overpick_requested(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        [$date, $part, $do] = $this->seedBaseData();
        $pick = OutgoingPickingFg::create([
            'delivery_date' => $date,
            'gci_part_id' => $part->id,
            'delivery_order_id' => $do->id,
            'source' => 'daily_plan',
            'qty_plan' => 10,
            'qty_picked' => 8,
            'status' => 'picking',
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/picking-fg/pick', [
            'date' => $date,
            'part_no' => $part->part_no,
            'qty' => 5,
            'delivery_order_id' => $do->id,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.qty_picked', 10)
            ->assertJsonPath('data.qty_remaining', 0)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.applied_qty', 2)
            ->assertJsonPath('data.rejected_qty', 3);

        $pick->refresh();
        $do->refresh();
        $this->assertSame(10, (int) $pick->qty_picked);
        $this->assertSame('completed', $pick->status);
        $this->assertSame('completed', $do->status);
    }

    public function test_pick_requires_delivery_order_selection_when_part_exists_in_multiple_open_tasks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        [$date, $part] = $this->seedBaseData();
        $do1 = $this->makeDeliveryOrder('DO-PFG-1');
        $do2 = $this->makeDeliveryOrder('DO-PFG-2');

        OutgoingPickingFg::create([
            'delivery_date' => $date,
            'gci_part_id' => $part->id,
            'delivery_order_id' => $do1->id,
            'source' => 'daily_plan',
            'qty_plan' => 5,
            'qty_picked' => 1,
            'status' => 'picking',
            'created_by' => $user->id,
        ]);

        OutgoingPickingFg::create([
            'delivery_date' => $date,
            'gci_part_id' => $part->id,
            'delivery_order_id' => $do2->id,
            'source' => 'daily_plan',
            'qty_plan' => 7,
            'qty_picked' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/picking-fg/pick', [
            'date' => $date,
            'part_no' => $part->part_no,
            'qty' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('require_do_selection', true)
            ->assertJsonCount(2, 'options');
    }

    public function test_pick_rejects_when_task_already_completed(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        [$date, $part, $do] = $this->seedBaseData();
        OutgoingPickingFg::create([
            'delivery_date' => $date,
            'gci_part_id' => $part->id,
            'delivery_order_id' => $do->id,
            'source' => 'daily_plan',
            'qty_plan' => 4,
            'qty_picked' => 4,
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $this->postJson('/api/picking-fg/pick', [
            'date' => $date,
            'part_no' => $part->part_no,
            'qty' => 1,
            'delivery_order_id' => $do->id,
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * @return array{0:string,1:GciPart,2:DeliveryOrder}
     */
    private function seedBaseData(): array
    {
        $date = now()->toDateString();
        $part = GciPart::create([
            'part_no' => 'FG-PFG-01',
            'part_name' => 'FG PFG 01',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $do = $this->makeDeliveryOrder('DO-PFG-BASE');

        return [$date, $part, $do];
    }

    private function makeDeliveryOrder(string $doNo): DeliveryOrder
    {
        $customer = Customer::create([
            'code' => 'CUST-' . $doNo,
            'name' => 'Customer ' . $doNo,
            'status' => 'active',
        ]);

        return DeliveryOrder::create([
            'do_no' => $doNo,
            'customer_id' => $customer->id,
            'do_date' => now()->toDateString(),
            'status' => 'draft',
        ]);
    }
}
