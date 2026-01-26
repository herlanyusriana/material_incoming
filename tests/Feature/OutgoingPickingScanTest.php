<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\GciPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingPickingScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_picking_scan_updates_picked_qty_and_blocks_complete_picking_until_done(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'code' => 'CUST-1',
            'name' => 'Customer 1',
            'status' => 'active',
        ]);

        $gciPart = GciPart::create([
            'part_no' => 'FG-001',
            'part_name' => 'FG 001',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $dn = DeliveryNote::create([
            'dn_no' => 'DN-TEST-1',
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

        $this->actingAs($user)
            ->post(route('outgoing.delivery-notes.picking-scan.store', $dn), [
                'location_code' => 'A-01',
                'part_no' => 'FG-001',
                'qty' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertSame(1.0, (float) $item->fresh()->picked_qty);

        $this->actingAs($user)
            ->post(route('outgoing.delivery-notes.complete-picking', $dn))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('outgoing.delivery-notes.picking-scan.store', $dn), [
                'location_code' => 'A-01',
                'part_no' => 'FG-001',
                'qty' => 1,
            ])
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertSame(2.0, (float) $item->picked_qty);
        $this->assertNotNull($item->picked_at);
        $this->assertSame($user->id, $item->picked_by);

        $this->actingAs($user)
            ->post(route('outgoing.delivery-notes.complete-picking', $dn))
            ->assertSessionHas('success');

        $dn->refresh();
        $this->assertSame('ready_to_ship', $dn->status);
    }
}

