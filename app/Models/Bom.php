<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'status',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }

    public function items()
    {
        return $this->hasMany(BomItem::class);
    }
}
