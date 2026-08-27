<?php

namespace Tests\Feature;

use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

class WarehousePutawayTest extends TestCase
{
    use CreatesNewSchemaData;
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

        [$receive, $gciPart] = $this->makeIncomingChain('pass', 'TAG-1', qty: 10);

        $this->actingAs($user)
            ->post(route('warehouse.putaway.store', $receive), ['location_code' => 'A-01'])
            ->assertSessionHas('success');

        $this->assertSame('A-01', $receive->fresh()->location_code);

        $loc = InventoryLocationStock::query()
            ->where('gci_part_id', $gciPart->id)
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

        [$r1, $gciPart, $vendorPart, $arrivalItem] = $this->makeIncomingChain('pass', 'TAG-A', qty: 10);
        $r2 = $this->makeNewReceive($arrivalItem->id, 'pass', qty: 20, tag: 'TAG-B');

        $this->actingAs($user)
            ->post(route('warehouse.putaway.bulk'), [
                'location_code' => 'A-02',
                'receive_ids' => [$r1->id, $r2->id],
            ])
            ->assertSessionHas('success');

        $this->assertSame('A-02', $r1->fresh()->location_code);
        $this->assertSame('A-02', $r2->fresh()->location_code);

        $loc = InventoryLocationStock::query()
            ->where('gci_part_id', $gciPart->id)
            ->where('location_code', 'A-02')
            ->first();

        $this->assertNotNull($loc);
        $this->assertSame(30.0, (float) $loc->qty_on_hand);
    }
}
