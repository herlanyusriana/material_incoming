<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\FgInventory;
use App\Models\GciPart;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderShipCreatesDnTest extends TestCase
{
    use RefreshDatabase;

    public function test_ship_creates_dn_and_supports_partial_shipments(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'code' => 'CUST-1',
            'name' => 'Customer 1',
            'status' => 'active',
        ]);

        $part = GciPart::create([
            'part_no' => 'FG-001',
            'part_name' => 'FG 001',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $do = DeliveryOrder::create([
            'do_no' => 'DO-TEST-1',
            'customer_id' => $customer->id,
            'do_date' => now()->toDateString(),
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $item = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'gci_part_id' => $part->id,
            'qty_ordered' => 10,
            'qty_shipped' => 0,
        ]);

        FgInventory::create([
            'gci_part_id' => $part->id,
            'qty_on_hand' => 100,
        ]);

        $this->actingAs($user)
            ->post(route('outgoing.delivery-orders.ship', $do), [
                'items' => [
                    $item->id => ['qty' => 4],
                ],
            ])
            ->assertSessionHas('success');

        $do->refresh();
        $item->refresh();
        $this->assertSame('partial_shipped', $do->status);
        $this->assertSame(4.0, (float) $item->qty_shipped);

        $dn1 = DeliveryNote::query()->where('delivery_order_id', $do->id)->first();
        $this->assertNotNull($dn1);
        $this->assertSame('shipped', $dn1->status);
        $this->assertTrue(DnItem::query()->where('dn_id', $dn1->id)->where('gci_part_id', $part->id)->where('qty', 4)->exists());

        $this->assertSame(96.0, (float) FgInventory::where('gci_part_id', $part->id)->value('qty_on_hand'));

        $this->actingAs($user)
            ->post(route('outgoing.delivery-orders.ship', $do), [
                'items' => [
                    $item->id => ['qty' => 6],
                ],
            ])
            ->assertSessionHas('success');

        $do->refresh();
        $item->refresh();
        $this->assertSame('shipped', $do->status);
        $this->assertSame(10.0, (float) $item->qty_shipped);

        $this->assertSame(90.0, (float) FgInventory::where('gci_part_id', $part->id)->value('qty_on_hand'));
        $this->assertSame(2, DeliveryNote::query()->where('delivery_order_id', $do->id)->count());
    }
}

