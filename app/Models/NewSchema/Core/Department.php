<?php

namespace App\Models\NewSchema\Core;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends BaseModel
{
    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
        'created_by',
        'updated_by',
    ];

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}