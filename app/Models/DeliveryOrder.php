<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'do_no',
        'customer_id',
        'do_date',
        'status',
        'notes',
        'delivery_plan_id',
        'delivery_stop_id',
        'trip_no',
        'created_by',
    ];

    protected $casts = [
        'do_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class, 'delivery_order_id');
    }

    public function plan()
    {
        return $this->belongsTo(DeliveryPlan::class, 'delivery_plan_id');
    }

    public function stop()
    {
        return $this->belongsTo(DeliveryStop::class, 'delivery_stop_id');
    }

    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class, 'delivery_order_id');
    }

    public function deliveryNotes()
    {
        return $this->belongsToMany(DeliveryNote::class, 'delivery_note_delivery_order', 'delivery_order_id', 'delivery_note_id');
    }
}
