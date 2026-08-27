<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrderMaterialRequest extends BaseModel
{
    protected $table = 'production_order_material_requests';

    protected $fillable = [
        'production_order_id',
        'request_no',
        'request_date',
        'status',
        'notes',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterialRequestItem::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterialIssue::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeRequested($query)
    {
        return $query->where('status', 'requested');
    }
}