<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GciInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'gci_part_id',
        'batch_no',
        'on_hand',
        'on_order',
        'as_of_date',
    ];

    public function part()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    /**
     * Reserve material: move qty from on_hand to on_order.
     */
    public function reserve(float $qty): void
    {
        $this->decrement('on_hand', $qty);
        $this->increment('on_order', $qty);
        $this->update(['as_of_date' => now()->toDateString()]);
    }

    /**
     * Release reservation: move qty from on_order back to on_hand.
     */
    public function release(float $qty): void
    {
        $releaseQty = min($qty, (float) $this->on_order);
        $this->increment('on_hand', $releaseQty);
        $this->decrement('on_order', $releaseQty);
        $this->update(['as_of_date' => now()->toDateString()]);
    }

    /**
     * Consume reserved material: remove qty from on_order (already deducted from on_hand).
     */
    public function consume(float $qty): void
    {
        $consumeQty = min($qty, (float) $this->on_order);
        $this->decrement('on_order', $consumeQty);
        $this->update(['as_of_date' => now()->toDateString()]);
    }

    public function commitOrder(float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $this->increment('on_order', $qty);
        $this->update(['as_of_date' => now()->toDateString()]);
    }

    public function releaseOrder(float $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $releaseQty = min($qty, (float) $this->on_order);
        $this->decrement('on_order', $releaseQty);
        $this->update(['as_of_date' => now()->toDateString()]);
    }
}
