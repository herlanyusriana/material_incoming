<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mps extends Model
{
    use HasFactory;

    protected $table = 'mps';

    protected $fillable = [
        'forecast_qty',
        'open_order_qty',
        'planned_qty',
        'status',
        'approved_by',
        'approved_at',
        'part_id',
        'minggu',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
