<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'gci_part_id',
        'qty_ordered',
        'qty_shipped',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:4',
        'qty_shipped' => 'decimal:4',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}
