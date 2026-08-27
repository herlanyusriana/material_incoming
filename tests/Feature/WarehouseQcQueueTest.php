<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesNewSchemaData;
use Tests\TestCase;

class WarehouseQcQueueTest extends TestCase
{
    use CreatesNewSchemaData;
    use RefreshDatabase;

    public function test_qc_queue_requires_authentication(): void
    {
        $this->get(route('warehouse.qc.index'))->assertRedirect('/login');
    }

    public function test_qc_queue_shows_hold_and_can_update_to_pass_with_note(): void
    {
        $user = User::factory()->create();

        [$receive, $gciPart, $vendorPart, $arrivalItem] = $this->makeIncomingChain('hold', 'TAG-QC');

        $this->actingAs($user)
            ->get(route('warehouse.qc.index'))
            ->assertOk()
            ->assertSee('TAG-QC');

        $this->actingAs($user)
            ->post(route('warehouse.qc.update', $receive), [
                'qc_status' => 'pass',
                'qc_note' => 'OK after re-check',
            ])
            ->assertSessionHas('success');

        $receive->refresh();
        $this->assertSame('pass', $receive->qc_status);
        $this->assertSame('OK after re-check', $receive->qc_note);
        $this->assertNotNull($receive->qc_updated_at);
        $this->assertSame($user->id, $receive->qc_updated_by);
    }
}
