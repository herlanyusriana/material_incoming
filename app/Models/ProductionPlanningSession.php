<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanningSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_date',
        'planning_days',
        'status',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(ProductionPlanningLine::class, 'session_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get planning date range
     */
    public function getDateRangeAttribute(): array
    {
        $dates = [];
        for ($i = 0; $i < $this->planning_days; $i++) {
            $dates[] = $this->plan_date->copy()->addDays($i);
        }
        return $dates;
    }
}
