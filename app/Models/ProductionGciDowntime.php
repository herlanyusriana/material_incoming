<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionGciDowntime extends Model
{
    //
    protected $fillable = [
        'production_gci_work_order_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'reason',
        'notes',
        'offline_id'
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProductionGciWorkOrder::class, 'production_gci_work_order_id');
    }
}
