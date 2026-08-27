<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends BaseModel
{
    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'gci_part_id',
        'qty_ordered',
        'qty_delivered',
        'unit_price',
        'unit',
        'required_date',
        'notes',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:4',
        'qty_delivered' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'required_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function deliveryPlanningLines(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryPlanningLine::class);
    }

    public function deliveryNoteItems(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryNoteItem::class);
    }

    public function deliveryRequirements(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryRequirement::class);
    }
}