<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractNumberItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number_id',
        'gci_part_id',
        'rm_gci_part_id',
        'bom_item_id',
        'process_type',
        'target_qty',
        'warning_limit_qty',
    ];

    protected $casts = [
        'target_qty' => 'decimal:4',
        'warning_limit_qty' => 'decimal:4',
    ];

    public function contractNumber()
    {
        return $this->belongsTo(ContractNumber::class);
    }

    public function gciPart()
    {
        return $this->belongsTo(GciPart::class, 'gci_part_id');
    }

    public function rmPart()
    {
        return $this->belongsTo(GciPart::class, 'rm_gci_part_id');
    }

    public function bomItem()
    {
        return $this->belongsTo(BomItem::class, 'bom_item_id');
    }

    protected $appends = ['sent_qty', 'rejected_qty', 'remaining_qty'];

    public function getSentQtyAttribute()
    {
        // Calculate sent qty from SubconOrder
        return SubconOrder::where('contract_no', $this->contractNumber?->contract_no)
            ->where('gci_part_id', $this->gci_part_id)
            ->where('rm_gci_part_id', $this->rm_gci_part_id)
            ->where('process_type', $this->process_type)
            // considering all status that deducted from WH (sent, partial, completed), not cancelled.
            ->whereIn('status', ['sent', 'partial', 'completed'])
            ->sum('qty_sent');
    }

    public function getRejectedQtyAttribute()
    {
        return SubconOrder::where('contract_no', $this->contractNumber?->contract_no)
            ->where('gci_part_id', $this->gci_part_id)
            ->where('rm_gci_part_id', $this->rm_gci_part_id)
            ->where('process_type', $this->process_type)
            ->whereIn('status', ['sent', 'partial', 'completed'])
            ->sum('qty_rejected');
    }

    public function getRemainingQtyAttribute()
    {
        return max(0, (float) $this->target_qty - (float) $this->sent_qty - (float) $this->rejected_qty);
    }
}
