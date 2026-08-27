<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrderMaterialIssue extends BaseModel
{
    protected $table = 'production_order_material_issues';

    protected $fillable = [
        'production_order_id',
        'material_request_id',
        'issue_no',
        'issue_date',
        'notes',
        'issued_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderMaterialRequest::class, 'material_request_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterialIssueItem::class);
    }
}