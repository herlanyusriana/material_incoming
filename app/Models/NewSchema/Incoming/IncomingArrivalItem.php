<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\VendorPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingArrivalItem extends BaseModel
{
    protected $table = 'incoming_arrival_items';

    protected $fillable = [
        'arrival_id',
        'gci_part_id',
        'vendor_part_id',
        'material_group',
        'qty_goods',
        'unit_goods',
        'qty_bundle',
        'unit_bundle',
        'weight_nett',
        'unit_weight',
        'weight_gross',
        'price',
        'total_price',
        'is_foc',
        'notes',
        'size',
        'purchase_order_item_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty_goods' => 'decimal:3',
        'qty_bundle' => 'decimal:3',
        'weight_nett' => 'decimal:3',
        'weight_gross' => 'decimal:3',
        'price_unit' => 'decimal:3',
    ];

    public function arrival(): BelongsTo
    {
        return $this->belongsTo(IncomingArrival::class, 'arrival_id');
    }

    public function incomingArrival(): BelongsTo
    {
        return $this->belongsTo(IncomingArrival::class, 'arrival_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function vendorPart(): BelongsTo
    {
        return $this->belongsTo(VendorPart::class);
    }

    /**
     * Legacy alias for vendorPart - for backward compatibility with old code.
     */
    public function part(): BelongsTo
    {
        return $this->vendorPart();
    }

    public function receives(): HasMany
    {
        return $this->hasMany(IncomingReceive::class, 'arrival_item_id');
    }
}