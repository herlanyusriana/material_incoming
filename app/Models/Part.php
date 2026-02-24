<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only model backed by a DATABASE VIEW.
 *
 * The `parts` view reads from `gci_part_vendor` + `gci_parts`.
 * DO NOT call create() / update() / save() on this model.
 * Use GciPartVendor model for all write operations.
 */
class Part extends Model
{
    use HasFactory;

    protected $table = 'parts';

    // View is read-only — no timestamps
    public $timestamps = false;

    protected $guarded = [];

    // ── Relationships (read-only) ──────────────────────

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function arrivalItems()
    {
        return $this->hasMany(ArrivalItem::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function locationInventory()
    {
        return $this->hasMany(LocationInventory::class);
    }

    public function binTransfers()
    {
        return $this->hasMany(BinTransfer::class);
    }

    /**
     * Get the Internal Master Part (GCI Part) associated with this Vendor Part
     */
    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    /**
     * Resolve the corresponding GciPartVendor record (writable).
     */
    public function vendorLink()
    {
        return $this->belongsTo(GciPartVendor::class, 'id', 'id');
    }
}
