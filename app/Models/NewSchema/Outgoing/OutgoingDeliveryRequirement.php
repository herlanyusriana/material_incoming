<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDeliveryRequirement extends BaseModel
{
    protected $table = 'outgoing_delivery_requirements';

    protected $fillable = [
        'sales_order_item_id',
        'gci_part_id',
        'required_date',
        'qty_required',
        'qty_fulfilled',
        'status',
        'notes',
    ];

    protected $casts = [
        'required_date' => 'date',
        'qty_required' => 'decimal:4',
        'qty_fulfilled' => 'decimal:4',
    ];

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryRequirementFulfillment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }
}