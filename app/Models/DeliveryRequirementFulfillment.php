<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryRequirementFulfillment extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_date',
        'row_id',
        'qty',
        'delivery_plan_id',
        'created_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'qty' => 'decimal:4',
    ];

    public function row()
    {
        return $this->belongsTo(OutgoingDailyPlanRow::class, 'row_id');
    }

    public function plan()
    {
        return $this->belongsTo(DeliveryPlan::class, 'delivery_plan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

