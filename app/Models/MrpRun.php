<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MrpRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'minggu',
        'status',
        'run_by',
        'run_at',
    ];

    protected $casts = [
        'run_at' => 'datetime',
    ];

    public function purchasePlans()
    {
        return $this->hasMany(MrpPurchasePlan::class);
    }

    public function productionPlans()
    {
        return $this->hasMany(MrpProductionPlan::class);
    }
}
