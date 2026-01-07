<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_part_no',
        'customer_part_name',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function components()
    {
        return $this->hasMany(CustomerPartComponent::class);
    }
}
