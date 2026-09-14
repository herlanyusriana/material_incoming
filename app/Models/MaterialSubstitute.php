<?php

namespace App\Models;

use App\Models\NewSchema\Core\VendorPart;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialSubstitute extends Model
{
    use HasFactory;

    protected $fillable = [
        'generic_part_id',
        'substitute_part_id',
        'vendor_part_id',
        'ratio',
        'priority',
        'status',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'ratio' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function genericPart()
    {
        return $this->belongsTo(GciPart::class, 'generic_part_id');
    }

    public function substitutePart()
    {
        return $this->belongsTo(GciPart::class, 'substitute_part_id');
    }

    public function vendorPart()
    {
        return $this->belongsTo(VendorPart::class, 'vendor_part_id');
    }

    // ─── Legacy-compat aliases (per-BOM-line callers migrated to global) ───

    /** Alias of substitutePart() — old `substitutes.part` eager-loads. */
    public function part()
    {
        return $this->substitutePart();
    }

    /** Alias of substitutePart() — old `substitutes.gciPart` eager-loads. */
    public function gciPart()
    {
        return $this->substitutePart();
    }

    /** Alias of vendorPart() — old `substitutes.incomingPart` eager-loads. */
    public function incomingPart()
    {
        return $this->vendorPart();
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query, ?string $asOf = null)
    {
        $date = $asOf ?? now()->toDateString();

        return $query
            ->where('status', 'active')
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            });
    }
}