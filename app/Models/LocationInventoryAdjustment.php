<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationInventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'gci_part_id',
        'location_code',
        'batch_no',
        'from_location_code',
        'to_location_code',
        'from_batch_no',
        'to_batch_no',
        'action_type',
        'qty_before',
        'qty_after',
        'qty_change',
        'reason',
        'adjusted_at',
        'created_by',
    ];

    protected $casts = [
        'qty_before' => 'decimal:4',
        'qty_after' => 'decimal:4',
        'qty_change' => 'decimal:4',
        'adjusted_at' => 'datetime',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_code', 'location_code');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
