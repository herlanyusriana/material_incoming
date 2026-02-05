<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPo extends Model
{
    use HasFactory;

    protected $table = 'customer_pos';

    protected $fillable = [
        'po_no',
        'customer_id',
        'part_id',
        'period',
        'qty',
        'price',
        'amount',
        'status',
        'notes',
        'po_date',
        'delivery_date',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }
}
