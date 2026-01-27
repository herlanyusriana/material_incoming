<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'status',
    ];

    public function customerParts()
    {
        return $this->hasMany(CustomerPart::class);
    }

    public function gciParts()
    {
        return $this->hasMany(GciPart::class);
    }

    public function stockAtCustomers()
    {
        return $this->hasMany(StockAtCustomer::class);
    }
}
