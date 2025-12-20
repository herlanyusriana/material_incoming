<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalContainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_id',
        'container_no',
        'seal_code',
    ];

    public function arrival()
    {
        return $this->belongsTo(Arrival::class);
    }
}

