<?php

namespace Tests\Feature\Outgoing;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Driver;
use App\Models\GciPart;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_delivery_note_with_truck_and_driver()
    {
        $customer = Customer::factory()->create();
        $part = GciPart::factory()->create();
        $truck = Truck::create(['plate_no' => 'B 1234 GCI', 'type' => 'Wingbox', 'status' => 'available']);
        $driver = Driver::create(['name' => 'John Doe', 'status' => 'available']);

        $response = $this->actingAs($this->user)
            ->post(route('outgoing.delivery-notes.store'), [
                'dn_no' => 'DN-TEST-001',
                'customer_id' => $customer->id,
                'delivery_date' => now()->toDateString(),
                'truck_id' => $truck->id,
                'driver_id' => $driver->id,
                'items' => [
                    ['gci_part_id' => $part->id, 'qty' => 100]
                ]
            ]);

        $response->assertRedirect(route('outgoing.delivery-notes.index'));
        $this->assertDatabaseHas('delivery_notes', [
            'dn_no' => 'DN-TEST-001',
            'truck_id' => $truck->id,
            'driver_id' => $driver->id
        ]);
    }

    public function test_can_access_print_page()
    {
        $dn = DeliveryNote::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('outgoing.delivery-notes.print', $dn));

        $response->assertStatus(200);
        $response->assertSee($dn->dn_no);
        $response->assertSee('SURAT JALAN');
    }
}
