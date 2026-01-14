<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDailyPlanRow extends Model
{
    protected $table = 'outgoing_daily_plan_rows';

    protected $fillable = [
        'plan_id',
        'row_no',
        'production_line',
        'part_no',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(OutgoingDailyPlan::class, 'plan_id');
    }

    public function cells(): HasMany
    {
        return $this->hasMany(OutgoingDailyPlanCell::class, 'row_id');
    }
}

