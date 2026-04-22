<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionGciHourlyReport extends Model
{
    //
    protected $fillable = [
        'production_gci_work_order_id',
        'production_order_id',
        'machine_id',
        'machine_name',
        'time_range',
        'target',
        'actual',
        'ng',
        'ng_reason',
        'ng_scrap',
        'ng_rework',
        'ng_hold',
        'offline_id',
        'operator_name',
        'shift',
        'output_type',
        'process_name',
        'output_part_no',
        'output_part_name',
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProductionGciWorkOrder::class, 'production_gci_work_order_id');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }
}
