<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'component_part_id',
        'usage_qty',
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function componentPart()
    {
        return $this->belongsTo(Part::class, 'component_part_id');
    }
}
