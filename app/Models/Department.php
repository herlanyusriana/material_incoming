<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'status',
        'notes',
    ];

    public function inventories()
    {
        return $this->hasMany(ProductionInventory::class);
    }
}
