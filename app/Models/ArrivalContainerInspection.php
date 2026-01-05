<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalContainerInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_container_id',
        'status',
        'seal_code',
        'notes',
        'issues_left',
        'issues_right',
        'issues_front',
        'issues_back',
        'photo_left',
        'photo_right',
        'photo_front',
        'photo_back',
        'photo_inside',
        'photo_seal',
        'inspected_by',
    ];

    protected $casts = [
        'issues_left' => 'array',
        'issues_right' => 'array',
        'issues_front' => 'array',
        'issues_back' => 'array',
    ];

    public function container()
    {
        return $this->belongsTo(ArrivalContainer::class, 'arrival_container_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
