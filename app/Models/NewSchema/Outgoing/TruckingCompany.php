<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TruckingCompany extends BaseModel
{
    protected $table = 'trucking_companies';

    protected $fillable = [
        'company_code',
        'company_name',
        'address',
        'phone',
        'email',
        'contact_person',
        'status',
        'created_by',
        'updated_by',
    ];

    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}