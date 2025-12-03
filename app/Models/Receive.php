<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    protected $fillable = [
        'arrival_item_id',
        'tag',
        'qty',
        'ata_date',
        'qc_status',
        'weight',
        'jo_po_number',
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
