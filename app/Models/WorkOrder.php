<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'wo_no',
        'fg_part_id',
        'qty_plan',
        'plan_date',
        'status',
        'priority',
        'remarks',
        'source_type',
        'source_ref_type',
        'source_ref_id',
        'source_payload_json',
        'routing_json',
        'schedule_json',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'qty_plan' => 'decimal:4',
        'source_payload_json' => 'array',
        'routing_json' => 'array',
        'schedule_json' => 'array',
    ];

    public function fgPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class, 'fg_part_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function bomSnapshots(): HasMany
    {
        return $this->hasMany(WorkOrderBomSnapshot::class);
    }

    public function requirementSnapshots(): HasMany
    {
        return $this->hasMany(WorkOrderRequirementSnapshot::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WorkOrderHistory::class)->latest('id');
    }

    public static function generateWoNo(?Carbon $date = null): string
    {
        $date = $date ?: now();
        $prefix = 'WO-' . $date->format('Ymd') . '-';

        $last = static::query()
            ->where('wo_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('wo_no');

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

