<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'inventory_supply_id',
        'inventory_return_id',
        'department_id',
        'production_inventory_id',
        'gci_part_id',
        'part_id',
        'tag_number',
        'part_no',
        'part_name',
        'movement_type',
        'uom',
        'from_location_code',
        'to_location_code',
        'qty',
        'notes',
        'moved_at',
        'created_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'notes' => 'array',
        'moved_at' => 'datetime',
    ];
}
