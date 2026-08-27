<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\Machine;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionHourlyReport extends BaseModel
{
    protected $table = 'production_hourly_reports';

    protected $fillable = [
        'production_order_id',
        'work_order_id',
        'machine_id',
        'time_range',
        'target',
        'actual',
        'ng',
        'ng_reason',
        'ng_scrap',
        'ng_rework',
        'ng_hold',
        'output_type',
        'process_name',
        'output_part_no',
        'output_part_name',
        'operator_name',
        'shift',
        'offline_id',
        'machine_name',
        'ng_split',
        'wip_start',
        'wip_end',
        'actual_machine_id',
    ];

    protected $casts = [
        'target' => 'decimal:4',
        'actual' => 'decimal:4',
        'ng' => 'decimal:4',
        'ng_scrap' => 'decimal:4',
        'ng_rework' => 'decimal:4',
        'ng_hold' => 'decimal:4',
        'ng_split' => 'decimal:4',
        'wip_start' => 'decimal:4',
        'wip_end' => 'decimal:4',
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

    public function actualMachine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'actual_machine_id');
    }

    public function downtime(): BelongsTo
    {
        return $this->belongsTo(ProductionDowntime::class, 'offline_id');
    }
}