<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomItemSubstitute extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_item_id',
        'substitute_part_id',
        'ratio',
        'priority',
        'status',
        'notes',
    ];

    public function bomItem()
    {
        return $this->belongsTo(BomItem::class);
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'substitute_part_id');
    }
}

