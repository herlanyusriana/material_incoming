<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_id',
        'inspected_by',
        'status',
        'notes',
        'photo_left',
        'photo_right',
        'photo_front',
        'photo_back',
        'photo_inside',
        'issues_left',
        'issues_right',
        'issues_front',
        'issues_back',
        'issues_inside',
        'issues_seal',
    ];

    protected $casts = [
        'issues_left' => 'array',
        'issues_right' => 'array',
        'issues_front' => 'array',
        'issues_back' => 'array',
        'issues_inside' => 'array',
        'issues_seal' => 'array',
    ];

    public function arrival()
    {
        return $this->belongsTo(Arrival::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
