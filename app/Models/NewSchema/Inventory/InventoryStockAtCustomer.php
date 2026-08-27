<?php

namespace App\Models\NewSchema\Inventory;

use App\Models\NewSchema\BaseModel;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockAtCustomer extends BaseModel
{
    protected $table = 'inventory_stock_at_customers';

    protected $fillable = [
        'gci_part_id',
        'customer_id',
        'as_of_date',
        'qty',
        'location',
        'notes',
    ];

    protected $casts = [
        'as_of_date' => 'date',
        'qty' => 'decimal:4',
    ];

    public function gciPart(): BelongsTo
    {
        return $this->belongsTo(GciPart::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}