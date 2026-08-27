<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingDeliveryOrderItem extends BaseModel
{
    protected $table = 'outgoing_delivery_order_items';

    protected $fillable = [
        'delivery_order_id',
        'gci_part_id',
        'qty_ordered',
        'qty_delivered',
        'unit',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:4',
        'qty_delivered' => 'decimal:4',
        'unit_price' => 'decimal:4',
    ];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(OutgoingDeliveryOrder::class, 'delivery_order_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}