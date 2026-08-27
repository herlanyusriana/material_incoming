<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingJigPlan extends BaseModel
{
    protected $table = 'outgoing_jig_plans';

    protected $fillable = [
        'jig_setting_id',
        'delivery_planning_line_id',
        'planned_date',
        'qty',
        'status',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'qty' => 'decimal:4',
    ];

    public function jigSetting(): BelongsTo
    {
        return $this->belongsTo(OutgoingJigSetting::class, 'jig_setting_id');
    }

    public function deliveryPlanningLine(): BelongsTo
    {
        return $this->belongsTo(OutgoingDeliveryPlanningLine::class, 'delivery_planning_line_id');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}