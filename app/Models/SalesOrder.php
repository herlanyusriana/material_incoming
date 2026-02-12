<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_no',
        'customer_id',
        'so_date',
        'status',
        'notes',
        'delivery_plan_id',
        'delivery_stop_id',
        'trip_no',
        'created_by',
    ];

    protected $casts = [
        'so_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
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
        return $this->hasMany(DeliveryItem::class, 'sales_order_id');
    }

    public function deliveryNotes()
    {
        return $this->belongsToMany(DeliveryNote::class, 'delivery_items', 'sales_order_id', 'delivery_note_id');
    }
}

