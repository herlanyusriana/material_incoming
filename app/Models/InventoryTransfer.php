<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'part_id',
        'gci_part_id',
        'qty',
        'transfer_type',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
    ];

    /**
     * Get the source part (logistics)
     */
    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the target part (production)
     */
    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    /**
     * Get the user who created this transfer
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
