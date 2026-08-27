<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\User;
use App\Models\NewSchema\Production\ProductionOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySupply extends BaseModel
{
    protected $table = 'inventory_supplies';

    protected $fillable = [
        'supply_no',
        'production_order_id',
        'gci_part_id',
        'qty_supplied',
        'unit',
        'to_location_code',
        'supplied_by',
        'supplied_at',
        'notes',
    ];

    protected $casts = [
        'qty_supplied' => 'decimal:4',
        'supplied_at' => 'datetime',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function suppliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplied_by');
    }
}