<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_id',
        'part_id',
        'material_group',
        'size',
        'qty_bundle',
        'unit_bundle',
        'qty_goods',
        'unit_goods',
        'weight_nett',
        'unit_weight',
        'weight_gross',
        'price',
        'total_price',
        'notes',
    ];

    public function arrival()
    {
        return $this->belongsTo(Arrival::class);
    }

    public function receives()
    {
        return $this->hasMany(Receive::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
