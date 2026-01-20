<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    protected $fillable = [
        'arrival_item_id',
        'tag',
        'qty',
        'bundle_unit',
        'bundle_qty',
        'ata_date',
        'qc_status',
        'weight',
        'net_weight',
        'gross_weight',
        'qty_unit',
        'jo_po_number',
        'invoice_no',
        'delivery_note_no',
        'truck_no',
        'location_code',
    ];

    protected $casts = [
        'ata_date' => 'datetime',
    ];

    public function arrivalItem()
    {
        return $this->belongsTo(ArrivalItem::class);
    }
}
