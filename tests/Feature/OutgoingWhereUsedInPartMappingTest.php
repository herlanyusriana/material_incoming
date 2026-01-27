<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingWhereUsedInPartMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_mapping_page_renders_where_used_section(): void
    {
        $user = User::factory()->create();

        $resp = $this->actingAs($user)->get(route('outgoing.product-mapping'));

        $resp->assertOk();
        $resp->assertSee('Where-Used (BOM)');
    }

    public function test_where_used_endpoint_returns_json(): void
    {
        $user = User::factory()->create();

        $resp = $this->actingAs($user)->get(route('outgoing.where-used', [
            'part_no' => 'RM-001',
        ]));

        $resp->assertOk();
        $resp->assertJsonStructure([
            'part_no',
            'used_in',
        ]);
    }
}
