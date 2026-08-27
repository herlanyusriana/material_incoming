<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingDeliveryNoteItem extends BaseModel
{
    protected $table = 'outgoing_delivery_note_items';

    protected $fillable = [
        'delivery_note_id',
        'gci_part_id',
        'qty_delivered',
        'unit',
        'sales_order_item_id',
        'picking_fg_id',
        'batch_no',
        'from_location_code',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'qty_delivered' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:4',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(OutgoingDeliveryNote::class, 'delivery_note_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function pickingFg(): BelongsTo
    {
        return $this->belongsTo(OutgoingPickingFg::class, 'picking_fg_id');
    }

    public function requirementFulfillments()
    {
        return $this->hasMany(OutgoingDeliveryRequirementFulfillment::class);
    }
}