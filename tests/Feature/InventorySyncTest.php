<?php

namespace Tests\Feature;

use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

/**
 * The legacy location_inventory -> inventory sync was removed when the schema
 * was migrated to the NewSchema inventory model. Stock now lives in
 * inventory_location_stock (keyed by gci_part_id + location_code) and every
 * change is logged in inventory_stock_movements. These tests cover that path.
 */
class InventorySyncTest extends TestCase
{
    use CreatesNewSchemaData;
    use RefreshDatabase;

    public function test_location_inventory_syncs_to_inventory_on_create(): void
    {
        $user = User::factory()->create();
        $gciPart = $this->makeNewGciPart();

        InventoryLocationStock::updateStock($gciPart->id, 'A-01', 100.0, tag: 'TAG-1', createdBy: $user->id);

        $this->assertSame(100.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'A-01'));
        $this->assertSame(1, InventoryStockMovement::where('gci_part_id', $gciPart->id)->count());
    }

    public function test_location_inventory_syncs_to_inventory_on_update(): void
    {
        $user = User::factory()->create();
        $gciPart = $this->makeNewGciPart();

        InventoryLocationStock::updateStock($gciPart->id, 'A-01', 100.0, tag: 'TAG-1', createdBy: $user->id);
        InventoryLocationStock::updateStock($gciPart->id, 'A-01', 50.0, tag: 'TAG-2', createdBy: $user->id);

        $this->assertSame(150.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'A-01'));
    }

    public function test_location_inventory_syncs_multiple_locations_to_summary(): void
    {
        $user = User::factory()->create();
        $gciPart = $this->makeNewGciPart();

        InventoryLocationStock::updateStock($gciPart->id, 'A-01', 100.0, tag: 'TAG-1', createdBy: $user->id);
        InventoryLocationStock::updateStock($gciPart->id, 'B-02', 50.0, tag: 'TAG-2', createdBy: $user->id);
        InventoryLocationStock::updateStock($gciPart->id, 'C-03', 25.0, tag: 'TAG-3', createdBy: $user->id);

        $this->assertSame(100.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'A-01'));
        $this->assertSame(50.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'B-02'));
        $this->assertSame(25.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'C-03'));
    }

    public function test_location_inventory_syncs_to_gci_inventory(): void
    {
        $user = User::factory()->create();
        $gciPart = $this->makeNewGciPart();

        InventoryLocationStock::updateStock($gciPart->id, 'A-01', 200.0, tag: 'TAG-1', createdBy: $user->id, sourceReference: 'REF-1');

        $this->assertSame(200.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'A-01'));
        $movement = InventoryStockMovement::where('gci_part_id', $gciPart->id)->first();
        $this->assertNotNull($movement);
        $this->assertNotNull($movement->part_no);
    }

    public function test_location_inventory_sync_on_delete(): void
    {
        $user = User::factory()->create();
        $gciPart = $this->makeNewGciPart();

        InventoryLocationStock::updateStock($gciPart->id, 'A-01', 100.0, tag: 'TAG-1', createdBy: $user->id);
        InventoryLocationStock::updateStock($gciPart->id, 'A-01', -50.0, tag: 'TAG-2', createdBy: $user->id);

        $this->assertSame(50.0, InventoryLocationStock::getStockByLocation($gciPart->id, 'A-01'));
    }
}
