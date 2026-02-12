<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'dn_no',
        'customer_id',
        'truck_id',
        'driver_id',
        'status',
        'notes',
        'delivery_date',
        'delivered_at',
        'assigned_at',
        'created_by',
        'total_value',
        'delivery_plan_id',
        'delivery_stop_id',
        'sales_order_id',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class, 'truck_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function salesOrders()
    {
        return $this->belongsToMany(SalesOrder::class, 'delivery_items', 'delivery_note_id', 'sales_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}