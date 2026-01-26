<?php

namespace Tests\Feature;

use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\LocationInventory;
use App\Models\Part;
use App\Models\Receive;
use App\Models\User;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehousePutawayTest extends TestCase
{
    use RefreshDatabase;

    public function test_putaway_queue_requires_authentication(): void
    {
        $this->get(route('warehouse.putaway.index'))->assertRedirect('/login');
    }

    public function test_putaway_store_sets_location_and_updates_location_inventory(): void
    {
        $user = User::factory()->create();

        WarehouseLocation::create([
            'location_code' => 'A-01',
            'status' => 'ACTIVE',
        ]);

        $part = Part::create([
            'part_no' => 'RM-001',
            'status' => 'active',
        ]);

        $arrival = Arrival::create([
            'arrival_no' => 'ARR-2026-9999',
        ]);

        $arrivalItem = ArrivalItem::create([
            'arrival_id' => $arrival->id,
            'part_id' => $part->id,
            'qty_goods' => 10,
            'unit_goods' => 'PCS',
        ]);

        $receive = Receive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'TAG-1',
            'qty' => 10,
            'ata_date' => now(),
            'qc_status' => 'pass',
            'qty_unit' => 'PCS',
            'location_code' => null,
        ]);

        $this->actingAs($user)
            ->post(route('warehouse.putaway.store', $receive), ['location_code' => 'A-01'])
            ->assertSessionHas('success');

        $receive->refresh();
        $this->assertSame('A-01', $receive->location_code);

        $loc = LocationInventory::query()
            ->where('part_id', $part->id)
            ->where('location_code', 'A-01')
            ->first();

        $this->assertNotNull($loc);
        $this->assertSame(10.0, (float) $loc->qty_on_hand);
    }

    public function test_putaway_bulk_updates_multiple_receives_and_sums_location_inventory(): void
    {
        $user = User::factory()->create();

        WarehouseLocation::create([
            'location_code' => 'A-02',
            'status' => 'ACTIVE',
        ]);

        $part = Part::create([
            'part_no' => 'RM-002',
            'status' => 'active',
        ]);

        $arrival = Arrival::create([
            'arrival_no' => 'ARR-2026-9998',
        ]);

        $arrivalItem = ArrivalItem::create([
            'arrival_id' => $arrival->id,
            'part_id' => $part->id,
            'qty_goods' => 30,
            'unit_goods' => 'PCS',
        ]);

        $r1 = Receive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'TAG-A',
            'qty' => 10,
            'ata_date' => now(),
            'qc_status' => 'pass',
            'qty_unit' => 'PCS',
            'location_code' => null,
        ]);
        $r2 = Receive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'TAG-B',
            'qty' => 20,
            'ata_date' => now(),
            'qc_status' => 'pass',
            'qty_unit' => 'PCS',
            'location_code' => null,
        ]);

        $this->actingAs($user)
            ->post(route('warehouse.putaway.bulk'), [
                'location_code' => 'A-02',
                'receive_ids' => [$r1->id, $r2->id],
            ])
            ->assertSessionHas('success');

        $this->assertSame('A-02', $r1->fresh()->location_code);
        $this->assertSame('A-02', $r2->fresh()->location_code);

        $loc = LocationInventory::query()
            ->where('part_id', $part->id)
            ->where('location_code', 'A-02')
            ->first();

        $this->assertNotNull($loc);
        $this->assertSame(30.0, (float) $loc->qty_on_hand);
    }
}
