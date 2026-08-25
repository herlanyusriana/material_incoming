<?php

namespace Tests\Feature;

use App\Models\NewSchema\Core\GciPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for QA finding Q-001.
 *
 * GciPart::$fillable previously omitted size/model/consumption_policy/
 * subcount and policy-confirmed fields, so GciPart::create($data) and
 * ->update($data) silently dropped them (silent data-loss). These tests
 * guard against re-introducing that bug.
 */
class GciPartFillableRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_part_master_fields(): void
    {
        $user = User::factory()->create();

        $part = GciPart::create([
            'part_no' => 'REG-001',
            'part_name' => 'Regression Part',
            'classification' => 'RM',
            'status' => 'active',
            'size' => 'M10x1.5',
            'model' => 'MODEL-X',
            'customer_id' => null,
            'default_location' => 'WH-A',
            'consumption_policy' => 'per_unit',
            'is_backflush' => 1,
            'policy_confirmed_at' => now(),
            'policy_confirmed_by' => $user->id,
            'subcount_enabled' => 1,
            'subcount_uom' => 'PCE',
            'subcount_process_type' => 'PG',
            'created_by' => $user->id,
        ]);

        $fresh = GciPart::find($part->id);

        $this->assertSame('M10x1.5', $fresh->size);
        $this->assertSame('MODEL-X', $fresh->model);
        $this->assertSame('WH-A', $fresh->default_location);
        $this->assertSame('per_unit', $fresh->consumption_policy);
        $this->assertSame(1, $fresh->is_backflush);
        $this->assertSame(1, $fresh->subcount_enabled);
        $this->assertSame('PCE', $fresh->subcount_uom);
        $this->assertSame('PG', $fresh->subcount_process_type);
        $this->assertNotNull($fresh->policy_confirmed_at);
    }

    public function test_update_persists_part_master_fields(): void
    {
        $user = User::factory()->create();

        $part = GciPart::create([
            'part_no' => 'REG-002',
            'part_name' => 'Regression Part 2',
            'classification' => 'FG',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $part->update([
            'size' => 'A4',
            'model' => 'MODEL-Y',
            'consumption_policy' => 'per_bom',
            'subcount_uom' => 'SET',
            'updated_by' => $user->id,
        ]);

        $fresh = GciPart::find($part->id);

        $this->assertSame('A4', $fresh->size);
        $this->assertSame('MODEL-Y', $fresh->model);
        $this->assertSame('per_bom', $fresh->consumption_policy);
        $this->assertSame('SET', $fresh->subcount_uom);
    }

    public function test_part_master_fields_are_fillable(): void
    {
        foreach (['size', 'model', 'default_location', 'consumption_policy', 'is_backflush', 'subcount_enabled', 'subcount_uom', 'subcount_process_type', 'policy_confirmed_at', 'policy_confirmed_by'] as $field) {
            $this->assertTrue(
                (new GciPart)->isFillable($field),
                "{$field} harus fillable di GciPart"
            );
        }

        // subcount_fg_part_id / subcount_rm_part_id BUKAN kolom gci_parts
        // (disimpan lewat syncSubcountBomMapping) — harus tetap non-fillable.
        $this->assertFalse((new GciPart)->isFillable('subcount_fg_part_id'));
        $this->assertFalse((new GciPart)->isFillable('subcount_rm_part_id'));
    }
}
