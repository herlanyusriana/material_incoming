<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderRequirementSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'component_part_id',
        'component_part_no',
        'component_part_name',
        'uom',
        'qty_per_fg',
        'qty_requirement',
    ];

    protected $casts = [
        'qty_per_fg' => 'decimal:6',
        'qty_requirement' => 'decimal:6',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}

