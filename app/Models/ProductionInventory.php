<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'machine_id',
        'code',
        'name',
        'inventory_type',
        'location_code',
        'status',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
