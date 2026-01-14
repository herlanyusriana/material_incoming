<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDailyPlan extends Model
{
    protected $table = 'outgoing_daily_plans';

    protected $fillable = [
        'date_from',
        'date_to',
        'created_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(OutgoingDailyPlanRow::class, 'plan_id')->orderBy('row_no')->orderBy('id');
    }
}

