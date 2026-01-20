<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forecast extends Model
{
    use HasFactory;

    protected $table = 'forecasts';

    protected $fillable = [
        'part_id',
        'minggu',
        'qty',
        'planning_qty',
        'po_qty',
        'source',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'planning_qty' => 'decimal:3',
        'po_qty' => 'decimal:3',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }
}
