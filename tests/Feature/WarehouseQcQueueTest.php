<?php

namespace Tests\Feature;

use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\Part;
use App\Models\Receive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseQcQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_qc_queue_requires_authentication(): void
    {
        $this->get(route('warehouse.qc.index'))->assertRedirect('/login');
    }

    public function test_qc_queue_shows_hold_and_can_update_to_pass_with_note(): void
    {
        $user = User::factory()->create();

        $part = Part::create([
            'part_no' => 'RM-010',
            'status' => 'active',
        ]);

        $arrival = Arrival::create([
            'arrival_no' => 'ARR-2026-9000',
        ]);

        $arrivalItem = ArrivalItem::create([
            'arrival_id' => $arrival->id,
            'part_id' => $part->id,
            'qty_goods' => 1,
            'unit_goods' => 'PCS',
        ]);

        $receive = Receive::create([
            'arrival_item_id' => $arrivalItem->id,
            'tag' => 'TAG-QC',
            'qty' => 1,
            'ata_date' => now(),
            'qc_status' => 'hold',
        ]);

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

