<?php

namespace Tests\Feature;

use App\Models\GciPart;
use App\Models\PricingMaster;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialPriceTest extends TestCase
{
    use RefreshDatabase;

    private function purchaseRow(array $overrides = []): PricingMaster
    {
        $part = GciPart::create([
            'part_no' => 'RM-MP-001',
            'part_name' => 'Resin A',
            'classification' => 'RM',
            'status' => 'active',
        ]);

        return PricingMaster::create(array_merge([
            'gci_part_id' => $part->id,
            'price_type' => 'purchase_price',
            'currency' => 'IDR',
            'price' => 15000,
            'effective_from' => '2026-09-01',
            'status' => 'active',
        ], $overrides));
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/purchasing/material-prices')->assertRedirect('/login');
    }

    public function test_user_without_purchase_permission_is_forbidden(): void
    {
        // 'staff' role has no manage_purchasing
        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get('/purchasing/material-prices')
            ->assertForbidden();
    }

    public function test_index_only_lists_buy_side_price_types(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'purchasing']));

        $purchase = $this->purchaseRow();
        $this->purchaseRow(['price_type' => 'material_cost']);
        $selling = $this->purchaseRow(['price_type' => 'selling_price', 'customer_id' => null]);

        $response = $this->get('/purchasing/material-prices')->assertOk();

        $this->assertTrue($response->viewData('prices')->contains('id', $purchase->id));
        $this->assertFalse($response->viewData('prices')->contains('id', $selling->id));
    }

    public function test_purchasing_user_can_create_purchase_price(): void
    {
        $user = User::factory()->create(['role' => 'purchasing']);
        $this->actingAs($user);

        $part = GciPart::create([
            'part_no' => 'RM-MP-002',
            'part_name' => 'Hardener',
            'classification' => 'RM',
            'status' => 'active',
        ]);
        $vendor = Vendor::create(['vendor_name' => 'PT Kimia', 'status' => 'active']);

        $response = $this->post('/purchasing/material-prices', [
            'gci_part_id' => $part->id,
            'vendor_id' => $vendor->id,
            'price_type' => 'purchase_price',
            'currency' => 'IDR',
            'price' => 25000,
            'effective_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('purchasing.material-prices.index'));

        $row = PricingMaster::where('gci_part_id', $part->id)->firstOrFail();
        $this->assertSame('purchase_price', $row->price_type);
        $this->assertSame($vendor->id, $row->vendor_id);
        $this->assertNull($row->customer_id);
        $this->assertSame($user->id, $row->created_by);
    }

    public function test_cannot_create_selling_price_from_material_price_screen(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'purchasing']));

        $part = GciPart::create([
            'part_no' => 'FG-MP-003',
            'part_name' => 'Casing',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        $response = $this->post('/purchasing/material-prices', [
            'gci_part_id' => $part->id,
            'price_type' => 'selling_price',
            'currency' => 'IDR',
            'price' => 99000,
            'effective_from' => '2026-09-01',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('price_type');
        $this->assertSame(0, PricingMaster::where('gci_part_id', $part->id)->count());
    }

    public function test_update_and_destroy_reject_rows_outside_purchase_types(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $selling = $this->purchaseRow(['price_type' => 'selling_price']);

        $this->put("/purchasing/material-prices/{$selling->id}", [
            'gci_part_id' => $selling->gci_part_id,
            'price_type' => 'selling_price',
            'currency' => 'IDR',
            'price' => 10,
            'effective_from' => '2026-09-01',
            'status' => 'active',
        ])->assertNotFound();

        $this->delete("/purchasing/material-prices/{$selling->id}")->assertNotFound();
        $this->assertDatabaseHas('pricing_masters', ['id' => $selling->id]);
    }

    public function test_admin_can_update_and_delete_purchase_row(): void
    {
        $this->actingAs(User::factory()->create());

        $row = $this->purchaseRow();

        $this->put("/purchasing/material-prices/{$row->id}", [
            'gci_part_id' => $row->gci_part_id,
            'price_type' => 'material_cost',
            'currency' => 'IDR',
            'price' => 17500,
            'effective_from' => '2026-09-01',
            'status' => 'inactive',
        ])->assertRedirect(route('purchasing.material-prices.index'));

        $row->refresh();
        $this->assertSame('material_cost', $row->price_type);
        $this->assertSame('17500.000', (string) $row->price);
        $this->assertSame('inactive', $row->status);

        $this->delete("/purchasing/material-prices/{$row->id}")
            ->assertRedirect(route('purchasing.material-prices.index'));
        $this->assertDatabaseMissing('pricing_masters', ['id' => $row->id]);
    }
}
