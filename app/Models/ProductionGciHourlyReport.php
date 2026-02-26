<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionGciHourlyReport extends Model
{
    //
    protected $fillable = [
        'production_gci_work_order_id',
        'time_range',
        'target',
        'actual',
        'ng',
        'offline_id'
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProductionGciWorkOrder::class, 'production_gci_work_order_id');
    }
}
