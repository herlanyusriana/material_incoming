<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = [
        'plate_no',
        'type',
        'capacity',
        'status',
    ];

    public function plans()
    {
        return $this->hasMany(DeliveryPlan::class);
    }
}
