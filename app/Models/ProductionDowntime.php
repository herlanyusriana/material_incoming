<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionDowntime extends Model
{
    protected $fillable = [
        'production_order_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'category',
        'notes',
        'created_by',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
