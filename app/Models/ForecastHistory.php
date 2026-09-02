<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastHistory extends Model
{
    protected $fillable = [
        'forecast_id',
        'qty_before',
        'qty_after',
        'changed_by',
        'action',
        'parts_count',
        'weeks_generated',
        'notes',
    ];

    /**
     * Display name of whoever made the change.
     */
    public function getChangedByNameAttribute(): ?string
    {
        return $this->changed_by;
    }
}
