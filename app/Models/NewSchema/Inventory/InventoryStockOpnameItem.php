<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockOpnameItem extends BaseModel
{
    protected $table = 'inventory_stock_opname_items';

    protected $fillable = [
        'opname_session_id',
        'gci_part_id',
        'location_code',
        'batch_no',
        'system_qty',
        'actual_qty',
        'notes',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'system_qty' => 'decimal:4',
        'actual_qty' => 'decimal:4',
        'difference' => 'decimal:4', // stored as generated column
        'counted_at' => 'datetime',
    ];

    public function opnameSession(): BelongsTo
    {
        return $this->belongsTo(InventoryStockOpnameSession::class, 'opname_session_id');
    }

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}