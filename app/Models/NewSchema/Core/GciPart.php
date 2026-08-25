<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GciPart extends BaseModel
{
    protected $table = 'gci_parts';

    protected $fillable = [
        'part_no',
        'part_name',
        'classification',
        'status',
        'size',
        'model',
        'customer_id',
        'default_location',
        'consumption_policy',
        'is_backflush',
        'policy_confirmed_at',
        'policy_confirmed_by',
        'subcount_enabled',
        'subcount_uom',
        'subcount_process_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'classification' => 'string',
    ];

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_parts')
            ->withPivot(['vendor_part_no', 'vendor_part_name', 'price', 'uom', 'hs_code', 'quality_inspection', 'status'])
            ->withTimestamps();
    }

    public function vendorParts(): HasMany
    {
        return $this->hasMany(VendorPart::class);
    }

    /**
     * Alias of vendorParts() — used by Parts Master views.
     */
    public function vendorLinks(): HasMany
    {
        return $this->hasMany(VendorPart::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_gci_part')->withTimestamps();
    }

    /**
     * Customer parts that use this GCI part as a component.
     */
    public function customerPartUsages(): HasMany
    {
        return $this->hasMany(CustomerPartComponent::class, 'gci_part_id');
    }

    public function customerPartComponents(): HasMany
    {
        return $this->hasMany(CustomerPartComponent::class);
    }

    public function incomingArrivalItems(): HasMany
    {
        return $this->hasMany(IncomingArrivalItem::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function inventoryLocationStock(): HasMany
    {
        return $this->hasMany(InventoryLocationStock::class);
    }

    public function outgoingDeliveryNoteItems(): HasMany
    {
        return $this->hasMany(OutgoingDeliveryNoteItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRawMaterial($query)
    {
        return $query->where('classification', 'RM');
    }

    public function scopeFinishedGood($query)
    {
        return $query->where('classification', 'FG');
    }
}