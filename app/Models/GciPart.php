<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GciPart extends Model
{
    use HasFactory;

    protected $table = 'gci_parts';

    protected $fillable = [
        'customer_id',
        'part_no',
        'barcode',
        'part_name',
        'model',
        'classification',
        'status',
        'net_weight',
        'gross_weight',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    private static function upperOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return strtoupper($string);
    }

    protected function partNo(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function partName(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value === null ? null : trim((string) $value),
        );
    }

    protected function model(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value === null ? null : trim((string) $value),
        );
    }

    public function forecasts()
    {
        return $this->hasMany(Forecast::class, 'part_id');
    }

    public function mps()
    {
        return $this->hasMany(Mps::class, 'part_id');
    }

    public function bom()
    {
        return $this->hasOne(Bom::class, 'part_id');
    }

    public function boms()
    {
        return $this->hasMany(Bom::class, 'part_id');
    }

    public function standardPacking()
    {
        return $this->hasOne(StandardPacking::class, 'gci_part_id');
    }

    public function stockAtCustomers()
    {
        return $this->hasMany(StockAtCustomer::class, 'gci_part_id');
    }

    /**
     * Generate barcode from part_no if not set
     */
    public function generateBarcode(): string
    {
        return $this->barcode ?: $this->part_no;
    }

    /**
     * Boot method to auto-generate barcode
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($part) {
            if (empty($part->barcode)) {
                $part->barcode = $part->part_no;
            }
        });
    }

    /**
     * Get BOM items where this part is used as a component.
     */
    public function componentUsages()
    {
        return $this->hasMany(BomItem::class, 'component_part_id');
    }

    public function vendorParts()
    {
        return $this->hasMany(Part::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'gci_part_vendor')
            ->withPivot(['vendor_part_no', 'vendor_part_name', 'register_no', 'price', 'uom', 'hs_code', 'quality_inspection', 'status'])
            ->withTimestamps();
    }

    public function vendorLinks()
    {
        return $this->hasMany(GciPartVendor::class);
    }

    /**
     * Customer parts that use this GCI part as a component.
     */
    public function customerPartUsages()
    {
        return $this->hasMany(CustomerPartComponent::class, 'gci_part_id');
    }
}
