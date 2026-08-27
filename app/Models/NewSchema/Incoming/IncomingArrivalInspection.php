<?php

namespace App\Models\NewSchema\Incoming;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingArrivalInspection extends BaseModel
{
    protected $table = 'incoming_arrival_inspections';

    protected $fillable = [
        'arrival_id',
        'inspected_by',
        'status',
        'notes',
        'issues_left',
        'issues_right',
        'issues_front',
        'issues_back',
        'issues_inside',
        'issues_seal',
        'photo_left',
        'photo_right',
        'photo_front',
        'photo_back',
        'photo_inside',
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

    public function arrival(): BelongsTo
    {
        return $this->belongsTo(IncomingArrival::class, 'arrival_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(IncomingArrivalInspectionIssue::class, 'arrival_inspection_id');
    }

    public function scopeOk($query)
    {
        return $query->where('status', 'ok');
    }

    public function scopeDamage($query)
    {
        return $query->where('status', 'damage');
    }
}