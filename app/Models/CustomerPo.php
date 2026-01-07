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
        'customer_part_no',
        'part_id',
        'minggu',
        'qty',
        'status',
        'notes',
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
