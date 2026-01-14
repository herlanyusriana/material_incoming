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
        'component_part_no',
        'usage_qty',
        'scrap_factor',
        'yield_factor',
        'consumption_uom',
        'consumption_uom_id',
        'line_no',
        'process_name',
        'machine_name',
        'wip_part_id',
        'wip_part_no',
        'wip_qty',
        'wip_uom',
        'wip_uom_id',
        'wip_part_name',
        'material_size',
        'material_spec',
        'material_name',
        'special',
        'make_or_buy',
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
        return $this->hasMany(BomItemSubstitute::class, 'bom_item_id');
    }

    public function consumptionUom()
    {
        return $this->belongsTo(Uom::class, 'consumption_uom_id');
    }

    public function wipUom()
    {
        return $this->belongsTo(Uom::class, 'wip_uom_id');
    }

    /**
     * Calculate net required quantity considering scrap and yield
     */
    public function getNetRequiredAttribute(): float
    {
        $base = (float) $this->usage_qty;
        $yield = (float) ($this->yield_factor ?: 1);
        $scrap = (float) ($this->scrap_factor ?: 0);
        
        return ($base / $yield) * (1 + $scrap);
    }
}
