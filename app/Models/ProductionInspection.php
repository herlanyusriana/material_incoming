<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionInspection extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'details' => 'array',
        'photo_evidence' => 'array',
        'inspected_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
