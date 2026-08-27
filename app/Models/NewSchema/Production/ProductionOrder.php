<?php

namespace App\Models\NewSchema\Production;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\Machine;
use App\Models\NewSchema\Core\User;
use App\Models\NewSchema\Outgoing\OutgoingDailyPlanCell;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends BaseModel
{
    protected $table = 'production_orders';

    protected $fillable = [
        'production_order_number',
        'transaction_no',
        'gci_part_id',
        'plan_date',
        'qty_planned',
        'qty_actual',
        'machine_id',
        'status',
        'workflow_stage',
        'start_time',
        'end_time',
        'material_requested_at',
        'material_issued_at',
        'material_handed_over_at',
        'fg_supplied_to_wh_at',
        'fg_handed_over_to_wh_at',
        'last_handover_at',
        'created_by',
        'material_requested_by',
        'material_issued_by',
        'material_handed_over_by',
        'fg_supplied_to_wh_by',
        'fg_handed_over_to_wh_by',
        'is_kanban_released',
        'kanban_released_at',
        'active_operator_started_at',
        'active_operator_username',
        'mps_id',
        'mrp_run_id',
        'planning_line_id',
        'daily_plan_cell_id',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'material_requested_at' => 'date',
        'material_issued_at' => 'date',
        'material_handed_over_at' => 'date',
        'fg_supplied_to_wh_at' => 'date',
        'fg_handed_over_to_wh_at' => 'date',
        'last_handover_at' => 'date',
        'kanban_released_at' => 'datetime',
        'active_operator_started_at' => 'datetime',
        'is_kanban_released' => 'boolean',
        'qty_planned' => 'decimal:4',
        'qty_actual' => 'decimal:4',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function dailyPlanCell(): BelongsTo
    {
        return $this->belongsTo(OutgoingDailyPlanCell::class, 'daily_plan_cell_id');
    }

    public function reservedMaterials(): HasMany
    {
        return $this->hasMany(ProductionOrderReservedMaterial::class);
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterialRequest::class);
    }

    public function materialIssues(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterialIssue::class);
    }

    public function hourlyReports(): HasMany
    {
        return $this->hasMany(ProductionHourlyReport::class);
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(ProductionDowntime::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(ProductionInspection::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProductionOrderActivity::class);
    }

    public function arrivals(): HasMany
    {
        return $this->hasMany(ProductionOrderArrival::class);
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(InventorySupply::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(InventoryReturn::class);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}