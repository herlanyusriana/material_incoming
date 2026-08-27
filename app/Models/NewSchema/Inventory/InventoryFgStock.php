<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryFgStock extends BaseModel
{
    protected $table = 'inventory_fg_stock';

    protected $fillable = [
        'gci_part_id',
        'qty_on_hand',
        'location_code',
        'last_updated_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:4',
        'last_updated_at' => 'datetime',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }
}