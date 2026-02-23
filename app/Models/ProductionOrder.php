<?php

namespace App\Models;

use App\Models\MrpRun;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Generate a unique transaction number for production orders.
     * Format: WO{4-digit sequence per day}{DDMMYY} — 12 characters total
     * Example: WO1234010226
     */
    public static function generateTransactionNo(string $date): string
    {
        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->format('dmy');
        $suffix = $dateStr;

        $lastOrder = self::where('transaction_no', 'like', 'WO%' . $suffix)
            ->orderByRaw('LENGTH(transaction_no) DESC, transaction_no DESC')
            ->first();

        $nextSeq = 1;
        if ($lastOrder) {
            $seqStr = substr($lastOrder->transaction_no, 2, strlen($lastOrder->transaction_no) - 2 - strlen($suffix));
            $nextSeq = ((int) $seqStr) + 1;
        }

        return 'WO' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT) . $suffix;
    }

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

    /**
     * Linked arrivals (SO) for traceability: WO ↔ SO
     */
    public function arrivals()
    {
        return $this->belongsToMany(Arrival::class, 'production_order_arrivals')
            ->withTimestamps();
    }

    public function downtimes()
    {
        return $this->hasMany(ProductionDowntime::class, 'production_order_id');
    }

    public function getTotalDowntimeMinutesAttribute()
    {
        return $this->downtimes()->sum('duration_minutes') ?? 0;
    }
}


