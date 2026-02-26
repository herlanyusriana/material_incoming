<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionGciMaterialLot extends Model
{
    //
    protected $fillable = [
        'production_gci_work_order_id',
        'invoice_or_tag',
        'qty',
        'actual',
        'offline_id'
    ];

    public function workOrder()
    {
        return $this->belongsTo(ProductionGciWorkOrder::class, 'production_gci_work_order_id');
    }
}
