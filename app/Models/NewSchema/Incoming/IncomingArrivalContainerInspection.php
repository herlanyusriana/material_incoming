<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomingArrivalContainerInspection extends BaseModel
{
    protected $table = 'incoming_arrival_container_inspections';

    protected $fillable = [
        'arrival_container_id',
        'status',
        'driver_name',
        'seal_code',
        'issues_left',
        'issues_right',
        'issues_front',
        'issues_back',
        'issues_inside',
        'issues_seal',
        'seal_condition',
        'container_condition',
        'notes',
        'photo_left',
        'photo_right',
        'photo_front',
        'photo_back',
        'photo_inside',
        'photo_seal',
        'photo_damage',
        'photo_damage_1',
        'photo_damage_2',
        'photo_damage_3',
        'inspected_by',
        'inspected_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issues_left' => 'array',
        'issues_right' => 'array',
        'issues_front' => 'array',
        'issues_back' => 'array',
        'issues_inside' => 'array',
        'issues_seal' => 'array',
        'inspected_at' => 'datetime',
    ];

    public function container(): BelongsTo
    {
        return $this->belongsTo(IncomingArrivalContainer::class, 'arrival_container_id');
    }
}