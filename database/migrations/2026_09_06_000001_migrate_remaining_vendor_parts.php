<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrates the remaining 385 vendor-part records from the legacy
 * `gci_part_vendor` table into the new-schema `vendor_parts` table.
 *
 * The original data-migration (2026_08_04_000006) skipped the copy
 * because `vendor_parts` already had 1 record (manually created),
 * causing the guard `DB::table('vendor_parts')->exists()` to bail out.
 *
 * This migration also adds the missing `hs_code` column to `vendor_parts`
 * so that HS Code auto-sync in ArrivalController works correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add hs_code column to vendor_parts (model already expects it).
        if (!Schema::hasColumn('vendor_parts', 'hs_code')) {
            Schema::table('vendor_parts', function (Blueprint $table) {
                $table->string('hs_code', 255)->nullable()->after('uom');
            });
        }

        // 2. Copy missing records from gci_part_vendor -> vendor_parts.
        if (!DB::getSchemaBuilder()->hasTable('gci_part_vendor')) {
            return; // Legacy table not present — nothing to migrate.
        }

        // Build a set of existing (gci_part_id, vendor_id) pairs to respect the unique constraint.
        $existingPairs = [];
        foreach (DB::table('vendor_parts')->select(['gci_part_id', 'vendor_id'])->get() as $r) {
            $existingPairs[$r->gci_part_id . '-' . $r->vendor_id] = true;
        }

        $legacyRows = DB::table('gci_part_vendor')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $toInsert = [];
        foreach ($legacyRows as $row) {
            $pairKey = $row['gci_part_id'] . '-' . $row['vendor_id'];
            if (isset($existingPairs[$pairKey])) {
                // Update hs_code on the existing record if it's missing.
                if (!empty($row['hs_code'])) {
                    DB::table('vendor_parts')
                        ->where('gci_part_id', $row['gci_part_id'])
                        ->where('vendor_id', $row['vendor_id'])
                        ->update(['hs_code' => $row['hs_code']]);
                }
                continue;
            }

            // Only copy if the referenced gci_part_id and vendor_id exist.
            $gciExists = DB::table('gci_parts')->where('id', $row['gci_part_id'])->exists();
            $vendorExists = DB::table('vendors')->where('id', $row['vendor_id'])->exists();

            if (!$gciExists || !$vendorExists) {
                continue;
            }

            $existingPairs[$pairKey] = true;

            $toInsert[] = [
                'id' => $row['id'],
                'gci_part_id' => $row['gci_part_id'],
                'vendor_id' => $row['vendor_id'],
                'vendor_part_no' => $row['vendor_part_no'] ?? '',
                'vendor_part_name' => $row['vendor_part_name'] ?? '',
                'register_no' => $row['register_no'] ?? null,
                'price' => $row['price'] ?? null,
                'currency' => null,
                'uom' => $row['uom'] ?? null,
                'hs_code' => $row['hs_code'] ?? null,
                'quality_inspection' => (bool) ($row['quality_inspection'] ?? false),
                'status' => $row['status'] ?? 'active',
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['updated_at'] ?? now(),
            ];
        }

        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 200) as $chunk) {
                DB::table('vendor_parts')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        // We cannot un-copy the migrated rows, but we can drop the hs_code column.
        if (Schema::hasColumn('vendor_parts', 'hs_code')) {
            Schema::table('vendor_parts', function (Blueprint $table) {
                $table->dropColumn('hs_code');
            });
        }
    }
};
