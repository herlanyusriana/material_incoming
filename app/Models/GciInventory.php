<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GciInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'gci_part_id',
        'on_hand',
        'on_order',
        'as_of_date',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}

