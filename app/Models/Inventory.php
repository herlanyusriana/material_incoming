<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    protected $fillable = [
        'gci_part_id',
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
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function gciPart()
    {
        return $this->belongsTo(\App\Models\NewSchema\Core\GciPart::class, 'gci_part_id');
    }
}
