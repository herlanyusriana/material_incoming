<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNote;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNoteItem;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryOrder;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryOrderItem;
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
            'default_location' => 'LT-01',
        ]);

        $do = OutgoingDeliveryOrder::create([
            'do_no' => 'DO-TEST-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $item = OutgoingDeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'gci_part_id' => $part->id,
            'qty_ordered' => 10,
            'qty_delivered' => 0,
        ]);

        InventoryLocationStock::updateStock($part->id, 'LT-01', 100, tag: 'INIT', transactionType: 'RECEIVE');

        $this->actingAs($user)
            ->post(route('outgoing.delivery-orders.ship', $do), [
                'items' => [
                    $item->id => ['qty' => 4],
                ],
            ])
            ->assertSessionHas('success');

        $do->refresh();
        $item->refresh();
        $this->assertSame('partial', $do->status);
        $this->assertSame(4.0, (float) $item->qty_delivered);

        $dn = OutgoingDeliveryNote::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($dn);
        $this->assertSame('loaded', $dn->status);
        $this->assertTrue(
            OutgoingDeliveryNoteItem::query()
                ->where('delivery_note_id', $dn->id)
                ->where('gci_part_id', $part->id)
                ->where('qty_delivered', 4)
                ->exists()
        );

        $this->assertSame(96.0, InventoryLocationStock::getStockByLocation($part->id, 'LT-01'));

        $this->actingAs($user)
            ->post(route('outgoing.delivery-orders.ship', $do), [
                'items' => [
                    $item->id => ['qty' => 6],
                ],
            ])
            ->assertSessionHas('success');

        $do->refresh();
        $item->refresh();
        $this->assertSame('delivered', $do->status);
        $this->assertSame(10.0, (float) $item->qty_delivered);

        $this->assertSame(90.0, InventoryLocationStock::getStockByLocation($part->id, 'LT-01'));
        $this->assertSame(2, OutgoingDeliveryNote::query()->count());
    }
}
