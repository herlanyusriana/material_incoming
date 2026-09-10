<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionWorkOrder extends BaseModel
{
    protected $table = 'production_work_orders';

    protected $fillable = [
        'work_order_no',
        'gci_part_id',
        'bom_id',
        'qty_target',
        'qty_actual',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty_target' => 'decimal:4',
        'qty_actual' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function hourlyReports(): HasMany
    {
        return $this->hasMany(ProductionHourlyReport::class);
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(ProductionDowntime::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(WoRequirement::class, 'work_order_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(WoMaterialAllocation::class, 'work_order_id');
    }

    /**
     * Guard "gak boleh lepas dari tracking": WO hanya boleh closed
     * kalau semua alokasi sudah terselesaikan (CONSUMED / RETURNED / CANCELLED).
     */
    public function hasOpenAllocations(): bool
    {
        return $this->allocations()
            ->whereIn('status', ['RESERVED'])
            ->exists();
    }

    public function canBeClosed(): bool
    {
        return ! $this->hasOpenAllocations();
    }
}