<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockMovement extends BaseModel
{
    protected $table = 'inventory_stock_movements';

    protected $fillable = [
        'production_order_id',
        'inventory_supply_id',
        'inventory_return_id',
        'department_id',
        'production_inventory_id',
        'gci_part_id',
        'part_id',
        'tag_number',
        'batch_no',
        'part_no',
        'part_name',
        'movement_type',
        'transaction_type',
        'source_reference',
        'uom',
        'from_location_code',
        'to_location_code',
        'qty',
        'notes',
        'moved_at',
        'created_by',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'moved_at' => 'datetime',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}
