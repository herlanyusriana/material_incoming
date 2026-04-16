<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventorySupply extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'department_id',
        'production_inventory_id',
        'gci_part_id',
        'part_id',
        'tag_number',
        'part_no',
        'part_name',
        'uom',
        'consumption_policy',
        'status',
        'source_location_code',
        'target_location_code',
        'qty_supply',
        'qty_consumed',
        'qty_returned',
        'traceability',
        'supplied_at',
        'supplied_by',
    ];

    protected $casts = [
        'qty_supply' => 'decimal:4',
        'qty_consumed' => 'decimal:4',
        'qty_returned' => 'decimal:4',
        'traceability' => 'array',
        'supplied_at' => 'datetime',
    ];

    public function getQtyRemainingAttribute(): float
    {
        return max(0, round((float) $this->qty_supply - (float) $this->qty_consumed - (float) $this->qty_returned, 4));
    }

    public static function remainingQuantitySql(): string
    {
        return '(qty_supply - qty_consumed - qty_returned)';
    }

    public function scopeWithRemaining(Builder $query): Builder
    {
        return $query->selectRaw(self::remainingQuantitySql() . ' as qty_remaining_calculated');
    }

    public function scopeWhereRemainingPositive(Builder $query): Builder
    {
        return $query->whereRaw(self::remainingQuantitySql() . ' > 0');
    }

    public function scopeWhereRemainingEmpty(Builder $query): Builder
    {
        return $query->whereRaw(self::remainingQuantitySql() . ' <= 0');
    }

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function returns()
    {
        return $this->hasMany(InventoryReturn::class);
    }
}
