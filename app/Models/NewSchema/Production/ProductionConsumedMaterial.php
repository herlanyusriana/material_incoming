<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionConsumedMaterial extends BaseModel
{
    protected $table = 'production_consumed_materials';

    protected $fillable = [
        'production_order_id',
        'gci_part_id',
        'location_code',
        'batch_no',
        'qty_consumed',
        'source_receive_id',
        'source_arrival_id',
        'source_invoice_no',
        'inventory_stock_movement_id',
        'consumed_at',
        'consumed_by',
    ];

    protected $casts = [
        'qty_consumed' => 'decimal:4',
        'consumed_at' => 'datetime',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function sourceReceive(): BelongsTo
    {
        return $this->belongsTo(IncomingReceive::class, 'source_receive_id');
    }

    public function sourceArrival(): BelongsTo
    {
        return $this->belongsTo(IncomingArrival::class, 'source_arrival_id');
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryStockMovement::class, 'inventory_stock_movement_id');
    }

    public function consumedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumed_by');
    }
}
