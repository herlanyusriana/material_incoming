<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\GciPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OutgoingPickingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pick_endpoint_updates_picked_qty(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $customer = Customer::create([
            'code' => 'CUST-API',
            'name' => 'Customer API',
            'status' => 'active',
        ]);

        $gciPart = GciPart::create([
            'part_no' => 'FG-API-1',
            'part_name' => 'FG API 1',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $dn = DeliveryNote::create([
            'dn_no' => 'DN-API-1',
            'customer_id' => $customer->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'picking',
        ]);

        $item = DnItem::create([
            'dn_id' => $dn->id,
            'gci_part_id' => $gciPart->id,
            'qty' => 2,
            'picked_qty' => 0,
            'kitting_location_code' => 'A-01',
        ]);

        $this->postJson("/api/outgoing/delivery-notes/{$dn->id}/pick", [
            'location_code' => 'a-01',
            'part_no' => 'fg-api-1',
            'qty' => 1,
        ])->assertOk()->assertJson([
            'ok' => true,
            'item_id' => $item->id,
        ]);

        $this->assertSame(1.0, (float) $item->fresh()->picked_qty);
    }
}

