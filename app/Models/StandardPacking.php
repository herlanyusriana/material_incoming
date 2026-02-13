<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GciPart;

class StandardPacking extends Model
{
    protected $fillable = [
        'gci_part_id',
        'delivery_class',
        'packing_qty',
        'uom',
        'net_weight',
        'kemasan',
        'trolley_type',
        'status',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}
