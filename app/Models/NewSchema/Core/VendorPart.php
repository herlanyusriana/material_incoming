<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPart extends BaseModel
{
    protected $table = 'vendor_parts';

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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quality_inspection' => 'boolean',
        'price' => 'decimal:3',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}