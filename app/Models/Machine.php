<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'group_name',
        'cycle_time',
        'cycle_time_unit',
        'setup_time_minutes',
        'available_hours_per_shift',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function bomItems()
    {
        return $this->hasMany(BomItem::class);
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function planningLines()
    {
        return $this->hasMany(ProductionPlanningLine::class);
    }

    /**
     * Get cycle time normalized to seconds.
     */
    public function getCycleTimeInSeconds(): float
    {
        $ct = (float) $this->cycle_time;
        return match ($this->cycle_time_unit) {
            'minutes' => $ct * 60,
            'hours' => $ct * 3600,
            default => $ct, // seconds
        };
    }

    /**
     * Estimate production hours for a given quantity.
     * Formula: (qty × cycle_time_seconds / 3600) + (setup_time_minutes / 60)
     */
    public function estimateHours(float $qty): float
    {
        $cycleSeconds = $this->getCycleTimeInSeconds();
        $productionHours = ($qty * $cycleSeconds) / 3600;
        $setupHours = ((float) $this->setup_time_minutes) / 60;

        return round($productionHours + $setupHours, 2);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('group_name', 'like', "%{$search}%");
        });
    }
}
