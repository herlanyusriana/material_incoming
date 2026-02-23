<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingPickingFg extends Model
{
    protected $table = 'outgoing_picking_fgs';

    protected $fillable = [
        'delivery_date',
        'gci_part_id',
        'source',
        'outgoing_po_item_id',
        'delivery_order_id',
        'qty_plan',
        'qty_picked',
        'status',
        'pick_location',
        'picked_by',
        'picked_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'picked_at' => 'datetime',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function outgoingPoItem()
    {
        return $this->belongsTo(OutgoingPoItem::class);
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function picker()
    {
        return $this->belongsTo(\App\Models\User::class, 'picked_by');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getQtyRemainingAttribute(): int
    {
        return max(0, $this->qty_plan - $this->qty_picked);
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->qty_plan <= 0)
            return 0;
        return min(100, round(($this->qty_picked / $this->qty_plan) * 100, 1));
    }
}
