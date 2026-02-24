<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GciPartVendor extends Model
{
    use HasFactory;

    protected $table = 'gci_part_vendor';

    protected $fillable = [
        'gci_part_id',
        'vendor_id',
        'vendor_part_no',
        'vendor_part_name',
        'register_no',
        'price',
        'uom',
        'hs_code',
        'quality_inspection',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'quality_inspection' => 'boolean',
    ];

    private static function upperOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        return strtoupper(trim((string) $value));
    }

    protected function vendorPartNo(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function vendorPartName(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function registerNo(): Attribute
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

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
