<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mps extends Model
{
    use HasFactory;

    protected $table = 'mps';

    protected $fillable = [
        'part_id',
        'minggu',
        'forecast_qty',
        'open_order_qty',
        'planned_qty',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'forecast_qty' => 'decimal:3',
        'open_order_qty' => 'decimal:3',
        'planned_qty' => 'decimal:3',
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
