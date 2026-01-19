<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MrpProductionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'mrp_run_id',
        'part_id',
        'plan_date',
        'planned_qty',
    ];

    public function run()
    {
        return $this->belongsTo(MrpRun::class, 'mrp_run_id');
    }

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }
}
