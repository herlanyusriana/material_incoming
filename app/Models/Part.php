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
        'vendor_id',
        'status',
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
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function partNo(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function partNameVendor(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function partNameGci(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
        );
    }

    protected function hsCode(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::upperOrNull($value),
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

    public function usedInBomItems()
    {
        return $this->hasMany(BomItem::class, 'component_part_id');
    }
}
