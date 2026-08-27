<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterialRequestItem extends BaseModel
{
    protected $table = 'production_order_material_request_items';

    protected $fillable = [
        'material_request_id',
        'gci_part_id',
        'qty_requested',
        'qty_issued',
        'unit',
        'location_code',
        'batch_no',
        'notes',
    ];

    protected $casts = [
        'qty_requested' => 'decimal:4',
        'qty_issued' => 'decimal:4',
    ];

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderMaterialRequest::class, 'material_request_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}