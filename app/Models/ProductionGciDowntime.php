<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionGciDowntime extends Model
{
    //
    protected $fillable = [
        'production_gci_work_order_id',
        'machine_id',
        'machine_name',
        'shift',
        'start_time',
        'end_time',
        'duration_minutes',
        'reason',
        'operator_name',
        'notes',
        'refill_part_no',
        'refill_part_name',
        'refill_qty',
        'offline_id',
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProductionGciWorkOrder::class, 'production_gci_work_order_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
