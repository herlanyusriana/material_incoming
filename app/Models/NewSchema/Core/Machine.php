<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends BaseModel
{
    protected $table = 'machines';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function productionHourlyReports(): HasMany
    {
        return $this->hasMany(ProductionHourlyReport::class);
    }

    public function productionDowntimes(): HasMany
    {
        return $this->hasMany(ProductionDowntime::class);
    }
}