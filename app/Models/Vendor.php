<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'vendor_name',
        'address',
        'bank_account',
        'contact_person',
        'email',
        'phone',
        'status',
        'signature_path',
    ];

    public function parts()
    {
        return $this->hasMany(Part::class);
    }

    public function arrivals()
    {
        return $this->hasMany(Arrival::class);
    }
}
