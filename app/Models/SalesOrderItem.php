<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'gci_part_id',
        'qty_ordered',
        'qty_shipped',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:4',
        'qty_shipped' => 'decimal:4',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}

