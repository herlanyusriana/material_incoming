<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FgInventory extends Model
{
    use HasFactory;

    protected $table = 'fg_inventory';

    protected $fillable = [
        'gci_part_id',
        'qty_on_hand',
        'location',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }
}
