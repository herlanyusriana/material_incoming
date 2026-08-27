<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterialLot extends BaseModel
{
    protected $table = 'production_material_lots';

    protected $fillable = [
        'production_order_id',
        'gci_part_id',
        'lot_no',
        'batch_no',
        'qty',
        'unit',
        'location_code',
        'status',
        'received_at',
        'consumed_at',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'received_at' => 'datetime',
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

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}