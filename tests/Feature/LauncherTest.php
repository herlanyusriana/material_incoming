<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LauncherTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_home(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_home_renders_launcher_grid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/home')
            ->assertOk()
            ->assertSee('Master Data');
    }

    public function test_module_dashboard_renders_with_menu_tiles(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/module/master-data')
            ->assertOk()
            ->assertSee(__('modules.master_data'));
    }

    public function test_production_module_exposes_manual_work_order_tile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/module/production')
            ->assertOk()
            ->assertSee(__('modules.wo_tracking'))
            ->assertSee(route('production.wo-tracking.index'), false);
    }

    public function test_inventory_dashboard_renders_category_tabs_and_filtered_tiles(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/module/inventory?cat=rm')
            ->assertOk()
            ->assertSee(__('modules.inventory'))
            ->assertSee(__('modules.cat_rm'))
            ->assertSee(__('modules.incoming_material'))
            ->assertSee(__('modules.stock'))
            ->assertDontSee(__('modules.outgoing_material'));
    }

    public function test_legacy_warehouse_module_redirects_to_inventory(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/module/warehouse')
            ->assertRedirect('/module/inventory');
    }

    public function test_unknown_module_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/module/does-not-exist')->assertNotFound();
    }

    public function test_locale_can_be_switched_and_persisted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/locale/ko')->assertRedirect();

        $this->assertSame('ko', $user->fresh()->locale);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/locale/xx')->assertNotFound();
    }
}
