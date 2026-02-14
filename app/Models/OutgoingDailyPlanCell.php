<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingDailyPlanCell extends Model
{
    protected $table = 'outgoing_daily_plan_cells';

    protected $fillable = [
        'row_id',
        'plan_date',
        'seq',
        'qty',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'seq' => 'integer',
        'qty' => 'integer',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(OutgoingDailyPlanRow::class, 'row_id');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'daily_plan_cell_id');
    }
}
