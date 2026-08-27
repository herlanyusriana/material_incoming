<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\Machine;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionDowntime extends BaseModel
{
    protected $table = 'production_downtimes';

    protected $fillable = [
        'production_order_id',
        'work_order_id',
        'machine_id',
        'offline_id',
        'reason_category',
        'reason_code',
        'description',
        'start_time',
        'end_time',
        'duration_minutes',
        'operator_name',
        'shift',
        'refill_by',
        'refilled_at',
        'machine_name',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'refilled_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionWorkOrder::class, 'work_order_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function hourlyReports()
    {
        return $this->hasMany(ProductionHourlyReport::class, 'offline_id');
    }
}