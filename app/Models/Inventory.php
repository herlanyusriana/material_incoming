<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'on_hand',
        'on_order',
        'as_of_date',
    ];

    protected $casts = [
        'as_of_date' => 'date',
    ];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
