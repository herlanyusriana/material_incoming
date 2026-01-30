<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPlanRequirementAssignment extends Model
{
    protected $fillable = [
        'plan_date',
        'gci_part_id',
        'delivery_plan_id',
        'status',
        'line_type_override',
        'jig_capacity_nr1_override',
        'jig_capacity_nr2_override',
        'uph_nr1_override',
        'uph_nr2_override',
        'notes',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'uph_nr1_override' => 'decimal:2',
        'uph_nr2_override' => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(DeliveryPlan::class, 'delivery_plan_id');
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}
