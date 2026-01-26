<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_dashboard_requires_authentication(): void
    {
        $response = $this->get('/logistics');
        $response->assertRedirect('/login');
    }

    public function test_logistics_dashboard_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('logistics.dashboard'));

        $response->assertOk();
        $response->assertSee('Logistics Dashboard');
    }
}

