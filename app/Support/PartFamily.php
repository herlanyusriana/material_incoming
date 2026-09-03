<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Classifies a part into a product family by parsing its part_name.
 *
 * The rule mirrors the inline category determination in OutgoingController
 * (base/compressor base -> Comp Base, plate -> Back Plate, reinforce ->
 * Reinforce, tray -> Tray Drip, else -> Small Part) so the family concept has a
 * single source of truth. This is a derived, read-only grouping/label
 * dimension — it never influences MRP/BOM/make-buy computation.
 */
final class PartFamily
{
    /** Display order for the canonical family labels. */
    public const ORDER = [
        'Comp Base' => 1,
        'Back Plate' => 2,
        'Reinforce' => 3,
        'Tray Drip' => 4,
        'Small Part' => 5,
        'NON LG' => 6,
    ];

    /** Ordered, human-readable family labels (droppable into a <select>). */
    public const LABELS = [
        'Comp Base',
        'Back Plate',
        'Reinforce',
        'Tray Drip',
        'Small Part',
        'NON LG',
    ];

    public static function from(?string $partName): string
    {
        $n = strtolower(trim((string) $partName));

        if ($n === '' || $n === '-') {
            return 'Small Part';
        }

        if (str_contains($n, 'base') || str_contains($n, 'compressor base')) {
            return 'Comp Base';
        }
        if (str_contains($n, 'plate')) {
            return 'Back Plate';
        }
        if (str_contains($n, 'reinforce')) {
            return 'Reinforce';
        }
        if (str_contains($n, 'tray')) {
            return 'Tray Drip';
        }

        return 'Small Part';
    }

    /** Sort key so families render in the canonical order. */
    public static function order(string $family): int
    {
        return self::ORDER[$family] ?? 99;
    }

    public static function labels(): array
    {
        return self::LABELS;
    }
}
