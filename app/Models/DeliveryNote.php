<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'dn_no',
        'transaction_no',
        'customer_id',
        'status',
        'notes',
        'delivery_date',
        'delivered_at',
        'assigned_at',
        'created_by',
        'total_value',
        'delivery_plan_id',
        'delivery_stop_id',
        'delivery_order_id',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function getDeliveryNoteFileUrlAttribute(): ?string
    {
        if (!$this->delivery_note_file) {
            return null;
        }
        return Storage::disk('public')->url($this->delivery_note_file);
    }

    protected static function booted(): void
    {
        static::creating(function (DeliveryNote $deliveryNote) {
            if (empty($deliveryNote->dn_no)) {
                $deliveryNote->dn_no = self::generateDeliveryNoteNo();
            }
        });
    }

    public static function generateDeliveryNoteNo(): string
    {
        $year = now()->year;
        $lastDelivery = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $lastSequence = 0;
        if ($lastDelivery) {
            $parts = explode('-', $lastDelivery->dn_no);
            $lastSequence = (int) ($parts[2] ?? 0);
        }

        $next = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return 'DN-' . $year . '-' . $next;
    }

    /**
     * Generate a unique transaction number for delivery notes.
     * Format: DO{4-digit sequence per day}{DDMMYY} — 12 characters total
     * Example: DO1234010226
     */
    public static function generateTransactionNo(string $date): string
    {
        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->format('dmy');
        $suffix = $dateStr;

        $lastNote = self::where('transaction_no', 'like', 'DO%' . $suffix)
            ->orderByRaw('LENGTH(transaction_no) DESC, transaction_no DESC')
            ->first();

        $nextSeq = 1;
        if ($lastNote) {
            $seqStr = substr($lastNote->transaction_no, 2, strlen($lastNote->transaction_no) - 2 - strlen($suffix));
            $nextSeq = ((int) $seqStr) + 1;
        }

        return 'DO' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT) . $suffix;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }



    public function items()
    {
        return $this->hasMany(DnItem::class, 'dn_id');
    }

    public function deliveryOrders()
    {
        return $this->belongsToMany(DeliveryOrder::class, 'delivery_note_delivery_order', 'delivery_note_id', 'delivery_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Linked production orders (WO) for traceability: DO ↔ WO
     */
    public function productionOrders()
    {
        return $this->belongsToMany(ProductionOrder::class, 'delivery_note_production_orders')
            ->withTimestamps();
    }
}