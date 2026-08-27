<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends BaseModel
{
    protected $table = 'warehouse_locations';

    protected $fillable = [
        'location_code',
        'location_name',
        'zone',
        'rack',
        'shelf',
        'bin',
        'location_type',
        'is_active',
        'status',
        'created_by',
        'updated_by',
    ];

    public function inventoryLocationStocks(): HasMany
    {
        return $this->hasMany(InventoryLocationStock::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}