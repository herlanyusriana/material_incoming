<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'register_no',
        'part_no',
        'part_name_vendor',
        'part_name_gci',
        'hs_code',
        'vendor_id',
        'trucking_company',
        'storage_reg',
        'status',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function arrivalItems()
    {
        return $this->hasMany(ArrivalItem::class);
    }
}
