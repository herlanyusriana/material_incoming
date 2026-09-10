<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoRequirement extends BaseModel
{
    protected $table = 'wo_requirements';

    protected $fillable = [
        'work_order_id',
        'gci_part_id',
        'bom_item_id',
        'component_part_no',
        'required_qty',
        'uom',
        'consumption_policy',
    ];

    protected $casts = [
        'required_qty' => 'decimal:4',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionWorkOrder::class, 'work_order_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}
