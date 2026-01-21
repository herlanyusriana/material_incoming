<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BinTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'part_id',
        'from_location_code',
        'to_location_code',
        'qty',
        'transfer_date',
        'created_by',
        'notes',
        'status',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'transfer_date' => 'date',
    ];

    /**
     * Get the part being transferred
     */
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the source location
     */
    public function fromLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'from_location_code', 'location_code');
    }

    /**
     * Get the destination location
     */
    public function toLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'to_location_code', 'location_code');
    }

    /**
     * Get the user who created this transfer
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
