<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\User;
use App\Models\NewSchema\Production\ProductionOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReturn extends BaseModel
{
    protected $table = 'inventory_returns';

    protected $fillable = [
        'return_no',
        'production_order_id',
        'gci_part_id',
        'qty_returned',
        'unit',
        'from_location_code',
        'to_location_code',
        'reason',
        'returned_by',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'qty_returned' => 'decimal:4',
        'returned_at' => 'datetime',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}