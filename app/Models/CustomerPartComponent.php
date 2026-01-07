<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPartComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_part_id',
        'part_id',
        'usage_qty',
    ];

    public function customerPart()
    {
        return $this->belongsTo(CustomerPart::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }
}
