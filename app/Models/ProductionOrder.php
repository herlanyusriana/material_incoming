<?php

namespace App\Models;

use App\Models\MrpRun;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function mps()
    {
        return $this->belongsTo(Mps::class, 'mps_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inspections()
    {
        return $this->hasMany(ProductionInspection::class);
    }

    public function dailyPlanCell()
    {
        return $this->belongsTo(OutgoingDailyPlanCell::class, 'daily_plan_cell_id');
    }

    // Status Accessors

    public function getIsStartedAttribute()
    {
        return $this->status !== 'planned' && $this->status !== 'material_hold';
    }

    public function mrpRun()
    {
        return $this->belongsTo(MrpRun::class, 'mrp_run_id');
    }

    public function planningLine()
    {
        return $this->belongsTo(ProductionPlanningLine::class, 'planning_line_id');
    }
}

