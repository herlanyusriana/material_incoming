<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingPickingFg extends BaseModel
{
    protected $table = 'outgoing_picking_fg';

    protected $fillable = [
        'picking_no',
        'delivery_planning_line_id',
        'sales_order_id',
        'gci_part_id',
        'qty_picked',
        'unit',
        'from_location_code',
        'batch_no',
        'status',
        'picked_by',
        'picked_at',
        'notes',
    ];

    protected $casts = [
        'qty_picked' => 'decimal:4',
        'picked_at' => 'datetime',
    ];

    public function deliveryPlanningLine(): BelongsTo
    {
        return $this->belongsTo(OutgoingDeliveryPlanningLine::class, 'delivery_planning_line_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function deliveryNoteItems(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryNoteItem::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePicked($query)
    {
        return $query->where('status', 'picked');
    }
}