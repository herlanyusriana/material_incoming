<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPartComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_part_id',
        'gci_part_id',
        'qty_per_unit',
    ];

    public function customerPart()
    {
        return $this->belongsTo(CustomerPart::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}
