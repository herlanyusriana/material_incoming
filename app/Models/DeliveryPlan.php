<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPlan extends Model
{
    protected $fillable = [
        'plan_date',
        'sequence',
        'truck_id',
        'driver_id',
        'status',
        'estimated_departure',
        'estimated_return',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function stops()
    {
        return $this->hasMany(DeliveryStop::class, 'plan_id')->orderBy('sequence');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'delivery_plan_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'delivery_plan_id');
    }
}
