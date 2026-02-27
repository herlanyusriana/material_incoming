<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanningLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'gci_part_id',
        'machine_id',
        'process_name',
        'stock_fg_lg',
        'stock_fg_gci',
        'production_sequence',
        'plan_qty',
        'shift',
        'remark',
        'sort_order',
    ];

    protected $casts = [
        'stock_fg_lg' => 'decimal:4',
        'stock_fg_gci' => 'decimal:4',
        'plan_qty' => 'decimal:4',
    ];

    public function session()
    {
        return $this->belongsTo(ProductionPlanningSession::class, 'session_id');
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'planning_line_id');
    }

    /**
     * Get the machine_id from BOM if not set directly
     */
    public function getMachineFromBom(): ?int
    {
        if ($this->machine_id) {
            return $this->machine_id;
        }

        $bom = Bom::where('part_id', $this->gci_part_id)
            ->where('status', 'active')
            ->first();

        if ($bom) {
            $bomItem = $bom->items()
                ->whereNotNull('machine_id')
                ->first();

            return $bomItem->machine_id ?? null;
        }

        return null;
    }

    /**
     * Get the process name from BOM if not set directly
     */
    public function getProcessFromBom(): ?string
    {
        if ($this->process_name) {
            return $this->process_name;
        }

        $bom = Bom::where('part_id', $this->gci_part_id)
            ->where('status', 'active')
            ->first();

        if ($bom) {
            $bomItem = $bom->items()
                ->whereNotNull('process_name')
                ->where('process_name', '!=', '')
                ->first();

            return $bomItem->process_name ?? null;
        }

        return null;
    }
}
