<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAtCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'customer_id',
        'gci_part_id',
        'part_no',
        'part_name',
        'model',
        'status',
        'day_1',
        'day_2',
        'day_3',
        'day_4',
        'day_5',
        'day_6',
        'day_7',
        'day_8',
        'day_9',
        'day_10',
        'day_11',
        'day_12',
        'day_13',
        'day_14',
        'day_15',
        'day_16',
        'day_17',
        'day_18',
        'day_19',
        'day_20',
        'day_21',
        'day_22',
        'day_23',
        'day_24',
        'day_25',
        'day_26',
        'day_27',
        'day_28',
        'day_29',
        'day_30',
        'day_31',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}

