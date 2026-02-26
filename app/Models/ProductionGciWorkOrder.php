<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionGciWorkOrder extends Model
{
    //
    protected $fillable = [
        'order_no',
        'type_model',
        'tact_time',
        'target_uph',
        'date',
        'shift',
        'foreman',
        'operator_name',
        'offline_id'
    ];

    public function hourlyReports()
    {
        return $this->hasMany(ProductionGciHourlyReport::class);
    }

    public function downtimes()
    {
        return $this->hasMany(ProductionGciDowntime::class);
    }

    public function materialLots()
    {
        return $this->hasMany(ProductionGciMaterialLot::class);
    }
}
