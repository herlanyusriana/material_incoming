<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Trolly extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'kind',
        'qr_payload',
        'status',
    ];

    private static function upperOrNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : strtoupper($string);
    }

    public static function buildPayload(string $code, ?string $type = null, ?string $kind = null): string
    {
        $payload = [
            'type' => 'TROLLY_LOCATION',
            'code' => strtoupper(trim($code)),
            'troll_type' => self::upperOrNull($type),
            'kind' => self::upperOrNull($kind),
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }

    protected function kind(): Attribute
    {
        return Attribute::make(
            set: fn($value) => self::upperOrNull($value),
        );
    }
}
