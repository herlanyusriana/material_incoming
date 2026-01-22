<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'dn_no',
        'customer_id',
        'delivery_date',
        'status',
        'notes',
        'delivery_plan_id',
        'delivery_stop_id',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DnItem::class, 'dn_id');
    }

    public function plan()
    {
        return $this->belongsTo(DeliveryPlan::class, 'delivery_plan_id');
    }

    public function stop()
    {
        return $this->belongsTo(DeliveryStop::class, 'delivery_stop_id');
    }
}
