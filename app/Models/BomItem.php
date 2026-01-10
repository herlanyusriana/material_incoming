<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'line_no',
        'process_name',
        'machine_name',
        'wip_part_id',
        'wip_qty',
        'wip_uom',
        'wip_part_name',
        'material_size',
        'material_spec',
        'material_name',
        'special',
        'component_part_id',
        'make_or_buy',
        'usage_qty',
        'consumption_uom',
    ];

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function wipPart()
    {
        return $this->belongsTo(GciPart::class, 'wip_part_id');
    }

    public function componentPart()
    {
        return $this->belongsTo(GciPart::class, 'component_part_id');
    }

    public function substitutes()
    {
        return $this->hasMany(BomItemSubstitute::class);
    }
}
