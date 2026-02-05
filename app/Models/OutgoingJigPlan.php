<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingJigPlan extends Model
{
    protected $table = 'outgoing_jig_plans';

    protected $fillable = [
        'jig_setting_id',
        'plan_date',
        'jig_qty',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(OutgoingJigSetting::class, 'jig_setting_id');
    }
}
