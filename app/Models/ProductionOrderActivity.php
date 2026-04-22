<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderActivity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'qty_ok' => 'float',
        'qty_ng' => 'float',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
