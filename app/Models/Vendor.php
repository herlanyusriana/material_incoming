<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = [
        'is_complete',
        'missing_fields',
    ];

    protected $fillable = [
        'vendor_code',
        'vendor_name',
        'vendor_type',
        'country_code',
        'address',
        'bank_account',
        'contact_person',
        'email',
        'phone',
        'status',
        'signature_path',
    ];

    public function parts()
    {
        return $this->hasMany(Part::class);
    }

    public function arrivals()
    {
        return $this->hasMany(Arrival::class);
    }

    public function getMissingFieldsAttribute(): array
    {
        $requiredFields = [
            'country_code',
            'address',
            'bank_account',
            'contact_person',
            'email',
            'phone',
        ];

        $missing = [];
        foreach ($requiredFields as $field) {
            $value = $this->{$field};
            if ($value === null || trim((string) $value) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    public function getIsCompleteAttribute(): bool
    {
        return count($this->missing_fields) === 0;
    }
}
