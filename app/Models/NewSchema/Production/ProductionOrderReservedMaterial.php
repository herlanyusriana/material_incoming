<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderReservedMaterial extends BaseModel
{
    protected $table = 'production_order_reserved_materials';

    protected $fillable = [
        'production_order_id',
        'gci_part_id',
        'qty_reserved',
        'qty_consumed',
        'batch_no',
        'location_code',
        'reserved_at',
        'consumed_at',
        'reserved_by',
    ];

    protected $casts = [
        'qty_reserved' => 'decimal:4',
        'qty_consumed' => 'decimal:4',
        'reserved_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }
}