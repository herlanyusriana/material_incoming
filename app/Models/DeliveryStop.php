<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryStop extends Model
{
    protected $fillable = [
        'plan_id',
        'customer_id',
        'sequence',
        'estimated_arrival_time',
        'status',
    ];

    public function plan()
    {
        return $this->belongsTo(DeliveryPlan::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'delivery_stop_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'delivery_stop_id');
    }
}
