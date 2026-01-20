<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'revision',
        'effective_date',
        'end_date',
        'change_reason',
        'status',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'part_id');
    }

    public function items()
    {
        return $this->hasMany(BomItem::class)->orderBy('line_no');
    }

    /**
     * Get active BOM version for a part
     */
    public static function activeVersion($partId, $asOfDate = null)
    {
        $date = $asOfDate ?: now();
        
        return static::where('part_id', $partId)
            ->where('status', 'active')
            ->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Find where a component part is used
     */
    public static function whereUsed($partNo)
    {
        return static::with(['part', 'items.componentPart'])
            ->whereHas('items', function ($q) use ($partNo) {
                $q->where('component_part_no', $partNo)
                  ->orWhereHas('componentPart', function ($sub) use ($partNo) {
                      $sub->where('part_no', $partNo);
                  });
            })
            ->where('status', 'active')
            ->get();
    }

    /**
     * Create a new revision of this BOM
     */
    public function createNewRevision($changeReason = null)
    {
        // Get next revision letter
        $lastRevision = static::where('part_id', $this->part_id)
            ->orderBy('revision', 'desc')
            ->value('revision');
        
        $nextRevision = $lastRevision ? chr(ord($lastRevision) + 1) : 'B';
        
        // Close current revision
        $this->update(['end_date' => now()]);
        
        // Create new revision
        $newBom = $this->replicate();
        $newBom->revision = $nextRevision;
        $newBom->effective_date = now();
        $newBom->end_date = null;
        $newBom->change_reason = $changeReason;
        $newBom->save();
        
        // Copy all items to new revision
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->bom_id = $newBom->id;
            $newItem->save();
        }
        
        return $newBom;
    }

    /**
     * Recursively explode BOM to get all components with levels
     * Returns array of components with their hierarchy level and quantities
     */
    public function explode($parentQty = 1, $currentLevel = 0, $maxLevels = 10, &$visited = [])
    {
        if ($currentLevel >= $maxLevels) {
            return [];
        }

        // Prevent infinite loops
        $bomKey = $this->id;
        if (in_array($bomKey, $visited)) {
            return [];
        }
        $visited[] = $bomKey;

        $explosion = [];
        
        $this->loadMissing(['items.componentPart.bom', 'items.wipPart', 'items.consumptionUom', 'items.wipUom']);

        foreach ($this->items as $item) {
            $netQty = $item->net_required * $parentQty;
            
            $explosionItem = [
                'level' => $currentLevel,
                'line_no' => $item->line_no,
                'component_part_id' => $item->component_part_id,
                'component_part_no' => $item->component_part_no,
                'component_part' => $item->componentPart,
                'wip_part_id' => $item->wip_part_id,
                'wip_part_no' => $item->wip_part_no,
                'wip_part_name' => $item->wip_part_name,
                'wip_part' => $item->wipPart,
                'wip_qty' => $item->wip_qty,
                'wip_uom' => $item->wip_uom,
                'process_name' => $item->process_name,
                'machine_name' => $item->machine_name,
                'usage_qty' => $item->usage_qty,
                'scrap_factor' => $item->scrap_factor,
                'yield_factor' => $item->yield_factor,
                'net_required' => $item->net_required,
                'total_qty' => $netQty,
                'consumption_uom' => $item->consumption_uom,
                'material_size' => $item->material_size,
                'material_spec' => $item->material_spec,
                'material_name' => $item->material_name,
                'make_or_buy' => $item->make_or_buy,
                'special' => $item->special,
                'bom_item' => $item,
            ];

            $explosion[] = $explosionItem;

            // If component has its own BOM, recursively explode it
            if ($item->componentPart && $item->componentPart->bom) {
                $subBom = $item->componentPart->bom;
                $subExplosion = $subBom->explode($netQty, $currentLevel + 1, $maxLevels, $visited);
                $explosion = array_merge($explosion, $subExplosion);
            }
        }

        return $explosion;
    }

    /**
     * Get total material requirements from explosion
     */
    public function getTotalMaterialRequirements($quantity = 1)
    {
        $explosion = $this->explode($quantity);
        $materials = [];

        foreach ($explosion as $item) {
            $partNo = $item['component_part_no'];
            if (!isset($materials[$partNo])) {
                $materials[$partNo] = [
                    'part_no' => $partNo,
                    'part' => $item['component_part'],
                    'total_qty' => 0,
                    'uom' => $item['consumption_uom'],
                    'make_or_buy' => $item['make_or_buy'],
                    'material_spec' => $item['material_spec'],
                ];
            }
            $materials[$partNo]['total_qty'] += $item['total_qty'];
        }

        return array_values($materials);
    }
}

