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
    ];

    protected $casts = [
        'plan_date' => 'date',
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

