<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'period',
        'qty',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

