<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoMaterialAllocation extends BaseModel
{
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_CONSUMED = 'CONSUMED';
    public const STATUS_RETURNED = 'RETURNED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $table = 'wo_material_allocations';

    protected $fillable = [
        'work_order_id',
        'requirement_id',
        'gci_part_id',
        'tag',
        'location_code',
        'qty_reserved',
        'qty_consumed',
        'qty_returned',
        'status',
        'receive_id',
        'movement_ref',
        'allocated_by',
        'consumed_by',
        'allocated_at',
        'consumed_at',
        'returned_at',
    ];

    protected $casts = [
        'qty_reserved' => 'decimal:4',
        'qty_consumed' => 'decimal:4',
        'qty_returned' => 'decimal:4',
        'allocated_at' => 'datetime',
        'consumed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionWorkOrder::class, 'work_order_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function openQty(): float
    {
        return max(0, (float) $this->qty_reserved - (float) $this->qty_consumed - (float) $this->qty_returned);
    }
}
