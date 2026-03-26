<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingMaster extends Model
{
    use HasFactory;

    protected $table = 'pricing_masters';

    protected $fillable = [
        'gci_part_id',
        'vendor_id',
        'customer_id',
        'price_type',
        'currency',
        'uom',
        'min_qty',
        'price',
        'effective_from',
        'effective_to',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'min_qty' => 'decimal:3',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public const PRICE_TYPES = [
        'purchase_price' => 'Purchase Price',
        'material_cost' => 'Material Cost',
        'processing_cost' => 'Processing Cost',
        'standard_cost' => 'Standard Cost',
        'selling_price' => 'Selling Price',
        'osp_price' => 'OSP Price',
        'subcon_price' => 'Subcon Price',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
