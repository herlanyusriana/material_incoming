<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_supply_id',
        'production_order_id',
        'department_id',
        'production_inventory_id',
        'gci_part_id',
        'part_id',
        'tag_number',
        'uom',
        'from_location_code',
        'to_location_code',
        'qty_return',
        'notes',
        'returned_at',
        'returned_by',
    ];

    protected $casts = [
        'qty_return' => 'decimal:4',
        'notes' => 'array',
        'returned_at' => 'datetime',
    ];

    public function supply()
    {
        return $this->belongsTo(InventorySupply::class, 'inventory_supply_id');
    }
}
