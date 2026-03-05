<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderBomSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'bom_id',
        'bom_item_id',
        'line_no',
        'component_part_id',
        'component_part_no',
        'component_part_name',
        'usage_qty',
        'scrap_factor',
        'yield_factor',
        'net_required_per_fg',
        'consumption_uom',
        'process_name',
        'machine_name',
        'material_name',
        'material_spec',
        'material_size',
        'make_or_buy',
        'substitutes_json',
    ];

    protected $casts = [
        'usage_qty' => 'decimal:6',
        'scrap_factor' => 'decimal:6',
        'yield_factor' => 'decimal:6',
        'net_required_per_fg' => 'decimal:6',
        'substitutes_json' => 'array',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}

