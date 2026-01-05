<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'uom',
        'status',
    ];

    public function forecasts()
    {
        return $this->hasMany(Forecast::class);
    }

    public function customerOrders()
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function mps()
    {
        return $this->hasMany(Mps::class);
    }
}

