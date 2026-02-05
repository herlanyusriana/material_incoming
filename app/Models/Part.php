<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'register_no',
        'part_no',
        'part_name_vendor',
        'part_name_gci',
        'hs_code',
        'quality_inspection',
        'vendor_id',
        'gci_part_id',
        'status',
        'price',
        'uom',
    ];

    private static function upperOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return strtoupper($string);
    }

    protected function registerNo(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function partNo(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function partNameVendor(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function partNameGci(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function hsCode(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function qualityInspection(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

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
}
