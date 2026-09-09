<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubconRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_subcon_tile_points_to_subcon_create(): void
    {
        $tile = null;
        foreach (config('modules.modules') as $module) {
            foreach ($module['items'] ?? [] as $item) {
                if (($item['key'] ?? null) === 'subcon-request') {
                    $tile = $item;
                }
            }
        }

        $this->assertNotNull($tile, 'subcon-request tile must exist');
        $this->assertSame('subcon.create', $tile['route']);
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('subcon.create'));
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('subcon.create'))->assertRedirect(route('login', absolute: false));
    }

    public function test_ppic_can_open_the_request_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ppic']));
        $this->get(route('subcon.create'))->assertOk();
    }

    public function test_roles_without_manage_subcon_are_forbidden(): void
    {
        // 'purchasing' role has no manage_subcon
        $this->actingAs(User::factory()->create(['role' => 'purchasing']));
        $this->get(route('subcon.create'))->assertForbidden();
    }
}
