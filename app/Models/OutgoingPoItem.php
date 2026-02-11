<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingPoItem extends Model
{
    protected $table = 'outgoing_po_items';

    protected $fillable = [
        'outgoing_po_id',
        'vendor_part_name',
        'gci_part_id',
        'qty',
        'price',
        'delivery_date',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function outgoingPo()
    {
        return $this->belongsTo(OutgoingPo::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->qty * (float) $this->price;
    }
}
