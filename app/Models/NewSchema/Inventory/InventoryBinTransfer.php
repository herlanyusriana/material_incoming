<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBinTransfer extends BaseModel
{
    protected $table = 'inventory_bin_transfers';

    protected $fillable = [
        'transfer_no',
        'gci_part_id',
        'from_location_code',
        'to_location_code',
        'batch_no',
        'qty_transferred',
        'status',
        'transfer_type',
        'created_by',
        'completed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'qty_transferred' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }
}