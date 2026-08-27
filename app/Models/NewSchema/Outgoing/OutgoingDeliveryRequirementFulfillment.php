<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingDeliveryRequirementFulfillment extends BaseModel
{
    protected $table = 'outgoing_delivery_requirement_fulfillments';

    protected $fillable = [
        'requirement_id',
        'delivery_note_item_id',
        'qty_fulfilled',
    ];

    protected $casts = [
        'qty_fulfilled' => 'decimal:4',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(OutgoingDeliveryRequirement::class, 'requirement_id');
    }

    public function deliveryNoteItem(): BelongsTo
    {
        return $this->belongsTo(OutgoingDeliveryNoteItem::class, 'delivery_note_item_id');
    }
}