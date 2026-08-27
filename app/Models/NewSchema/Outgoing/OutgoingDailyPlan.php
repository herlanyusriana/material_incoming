<?php

namespace App\Models\NewSchema\Outgoing;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingDailyPlan extends BaseModel
{
    protected $table = 'outgoing_daily_plans';

    protected $fillable = [
        'date_from',
        'date_to',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(OutgoingDailyPlanRow::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}