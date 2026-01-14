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
}
