<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends BaseModel
{
    protected $table = 'vendors';

    protected $fillable = [
        'vendor_name',
        'vendor_code',
        'vendor_type',
        'address',
        'bank_account',
        'contact_person',
        'email',
        'phone',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
        'vendor_type' => 'string',
    ];

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(GciPart::class, 'vendor_parts')
            ->withPivot(['vendor_part_no', 'vendor_part_name', 'price', 'uom', 'hs_code', 'quality_inspection', 'status'])
            ->withTimestamps();
    }

    public function vendorParts(): HasMany
    {
        return $this->hasMany(VendorPart::class);
    }

    public function incomingArrivals(): HasMany
    {
        return $this->hasMany(IncomingArrival::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function subconOrders(): HasMany
    {
        return $this->hasMany(SubconOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeImport($query)
    {
        return $query->where('vendor_type', 'import');
    }

    public function scopeLocal($query)
    {
        return $query->where('vendor_type', 'local');
    }
}