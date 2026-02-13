<?php

namespace Tests\Feature;

use App\Models\GciInventory;
use App\Models\GciPart;
use App\Models\Inventory;
use App\Models\LocationInventory;
use App\Models\Part;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventorySyncTest extends TestCase
{
    use RefreshDatabase;

    private function createVendor(): Vendor
    {
        return Vendor::create([
            'vendor_name' => 'Test Vendor',
            'vendor_type' => 'import',
        ]);
    }

    private function createPart(int $vendorId, ?int $gciPartId = null): Part
    {
        return Part::create([
            'vendor_id' => $vendorId,
            'part_no' => 'TEST-' . rand(1000, 9999),
            'part_name_vendor' => 'Test Part',
            'status' => 'active',
            'gci_part_id' => $gciPartId,
        ]);
    }

    private function createGciPart(): GciPart
    {
        return GciPart::create([
            'part_no' => 'GCI-' . rand(1000, 9999),
            'part_name' => 'GCI Test Part',
            'classification' => 'RM',
            'status' => 'active',
        ]);
    }

    public function test_location_inventory_syncs_to_inventory_on_create()
    {
        // Arrange: Create vendor and part
        $vendor = $this->createVendor();
        $part = $this->createPart($vendor->id);

        // Act: Create location inventory
        $locationInventory = LocationInventory::create([
            'part_id' => $part->id,
            'gci_part_id' => null,
            'location_code' => 'A-01',
            'qty_on_hand' => 100,
        ]);

        // Assert: Inventory summary should be auto-created
        $inventory = Inventory::where('part_id', $part->id)->first();
        $this->assertNotNull($inventory);
        $this->assertEquals(100, $inventory->on_hand);
    }

    public function test_location_inventory_syncs_to_inventory_on_update()
    {
        // Arrange
        $vendor = $this->createVendor();
        $part = $this->createPart($vendor->id);

        $locationInventory = LocationInventory::create([
            'part_id' => $part->id,
            'location_code' => 'A-01',
            'qty_on_hand' => 100,
        ]);

        // Act: Update quantity
        $locationInventory->update(['qty_on_hand' => 150]);

        // Assert
        $inventory = Inventory::where('part_id', $part->id)->first();
        $this->assertEquals(150, $inventory->on_hand);
    }

    public function test_location_inventory_syncs_multiple_locations_to_summary()
    {
        // Arrange
        $vendor = $this->createVendor();
        $part = $this->createPart($vendor->id);

        // Act: Create stock in multiple locations
        LocationInventory::create([
            'part_id' => $part->id,
            'location_code' => 'A-01',
            'qty_on_hand' => 100,
        ]);

        LocationInventory::create([
            'part_id' => $part->id,
            'location_code' => 'B-02',
            'qty_on_hand' => 50,
        ]);

        LocationInventory::create([
            'part_id' => $part->id,
            'location_code' => 'C-03',
            'qty_on_hand' => 25,
        ]);

        // Assert: Total should be summed
        $inventory = Inventory::where('part_id', $part->id)->first();
        $this->assertEquals(175, $inventory->on_hand);
    }

    public function test_location_inventory_syncs_to_gci_inventory()
    {
        // Arrange
        $gciPart = $this->createGciPart();
        $vendor = $this->createVendor();
        $part = $this->createPart($vendor->id, $gciPart->id);

        // Act: Create location inventory with gci_part_id
        LocationInventory::create([
            'part_id' => $part->id,
            'gci_part_id' => $gciPart->id,
            'location_code' => 'A-01',
            'qty_on_hand' => 200,
        ]);

        // Assert: Both summaries should exist
        $inventory = Inventory::where('part_id', $part->id)->first();
        $gciInventory = GciInventory::where('gci_part_id', $gciPart->id)->first();

        $this->assertNotNull($inventory);
        $this->assertNotNull($gciInventory);
        $this->assertEquals(200, $inventory->on_hand);
        $this->assertEquals(200, $gciInventory->on_hand);
    }

    public function test_location_inventory_sync_on_delete()
    {
        // Arrange
        $vendor = $this->createVendor();
        $part = $this->createPart($vendor->id);

        $loc1 = LocationInventory::create([
            'part_id' => $part->id,
            'location_code' => 'A-01',
            'qty_on_hand' => 100,
        ]);

        $loc2 = LocationInventory::create([
            'part_id' => $part->id,
            'location_code' => 'B-02',
            'qty_on_hand' => 50,
        ]);

        // Verify initial state
        $inventory = Inventory::where('part_id', $part->id)->first();
        $this->assertEquals(150, $inventory->on_hand);

        // Act: Delete one location
        $loc1->delete();

        // Assert: Summary should be updated
        $inventory->refresh();
        $this->assertEquals(50, $inventory->on_hand);
    }
}
