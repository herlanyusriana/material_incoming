<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trucking extends Model
{
    protected $table = 'trucking_companies';

    protected $fillable = [
        'company_name',
        'address',
        'phone',
        'email',
        'contact_person',
        'status',
    ];

    public function arrivals()
    {
        return $this->hasMany(Arrival::class, 'trucking_company_id');
    }
}
