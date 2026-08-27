<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterialIssueItem extends BaseModel
{
    protected $table = 'production_order_material_issue_items';

    protected $fillable = [
        'material_issue_id',
        'gci_part_id',
        'qty_issued',
        'unit',
        'location_code',
        'batch_no',
    ];

    protected $casts = [
        'qty_issued' => 'decimal:4',
    ];

    public function materialIssue(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderMaterialIssue::class, 'material_issue_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}