<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'period',
        'qty',
        'status',
        'order_no',
        'customer_name',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

