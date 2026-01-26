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
        'qc_note',
        'qc_updated_at',
        'qc_updated_by',
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
        'qc_updated_at' => 'datetime',
    ];

    public function arrivalItem()
    {
        return $this->belongsTo(ArrivalItem::class);
    }

    public function qcUpdater()
    {
        return $this->belongsTo(User::class, 'qc_updated_by');
    }
}
