<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'location_code',
        'gci_part_id',
        'batch',
        'system_qty',
        'counted_qty',
        'counted_by',
        'counted_at',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'decimal:4',
        'counted_qty' => 'decimal:4',
        'difference' => 'decimal:4',
        'counted_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(StockOpnameSession::class, 'session_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_code', 'location_code');
    }
}
