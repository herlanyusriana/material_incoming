<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ─── 1A: Create gci_part_vendor pivot table ───
        if (!Schema::hasTable('gci_part_vendor')) {
            Schema::create('gci_part_vendor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->string('vendor_part_no')->nullable();
                $table->string('vendor_part_name')->nullable();
                $table->string('register_no')->nullable();
                $table->decimal('price', 20, 3)->default(0);
                $table->string('uom')->nullable();
                $table->string('hs_code')->nullable();
                $table->boolean('quality_inspection')->default(false);
                $table->string('status')->default('active');
                $table->timestamps();
                $table->unique(['gci_part_id', 'vendor_id']);
            });
        }

        // ─── 1B: Migrate data from parts → gci_part_vendor ───
        // First ensure all parts have gci_part_id (auto-create GciPart RM if needed)
        $orphanParts = DB::table('parts')
            ->whereNull('gci_part_id')
            ->whereNotNull('vendor_id')
            ->get();

        foreach ($orphanParts as $orphan) {
            // Try to find existing GciPart by part_no match
            $gciPart = DB::table('gci_parts')
                ->whereRaw('UPPER(TRIM(part_no)) = ?', [strtoupper(trim($orphan->part_no))])
                ->first();

            if (!$gciPart) {
                // Auto-create as RM
                $gciPartId = DB::table('gci_parts')->insertGetId([
                    'part_no' => strtoupper(trim($orphan->part_no)),
                    'part_name' => $orphan->part_name_gci ?? $orphan->part_name_vendor,
                    'classification' => 'RM',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $gciPartId = $gciPart->id;
            }

            DB::table('parts')
                ->where('id', $orphan->id)
                ->update(['gci_part_id' => $gciPartId]);
        }

        // Now copy data, handling duplicates (keep first per gci_part_id+vendor_id)
        $vendorParts = DB::table('parts')
            ->whereNotNull('gci_part_id')
            ->whereNotNull('vendor_id')
            ->orderBy('id')
            ->get();

        $seen = [];
        foreach ($vendorParts as $vp) {
            $key = $vp->gci_part_id . '-' . $vp->vendor_id;
            if (isset($seen[$key])) {
                continue; // skip duplicate
            }
            $seen[$key] = true;

            // Check quality_inspection value type
            $qi = false;
            if ($vp->quality_inspection !== null) {
                $qiVal = strtoupper(trim((string) $vp->quality_inspection));
                $qi = in_array($qiVal, ['YES', '1', 'TRUE']);
            }

            DB::table('gci_part_vendor')->insertOrIgnore([
                'gci_part_id' => $vp->gci_part_id,
                'vendor_id' => $vp->vendor_id,
                'vendor_part_no' => $vp->part_no,
                'vendor_part_name' => $vp->part_name_vendor,
                'register_no' => $vp->register_no ?? null,
                'price' => $vp->price ?? 0,
                'uom' => $vp->uom,
                'hs_code' => $vp->hs_code,
                'quality_inspection' => $qi,
                'status' => $vp->status ?? 'active',
                'created_at' => $vp->created_at ?? now(),
                'updated_at' => $vp->updated_at ?? now(),
            ]);
        }

        // ─── 1C: Add bridge columns to dependent tables ───

        // arrival_items: add gci_part_id + gci_part_vendor_id
        Schema::table('arrival_items', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_items', 'gci_part_id')) {
                $table->unsignedBigInteger('gci_part_id')->nullable()->after('part_id');
                $table->foreign('gci_part_id')->references('id')->on('gci_parts')->nullOnDelete();
            }
            if (!Schema::hasColumn('arrival_items', 'gci_part_vendor_id')) {
                $table->unsignedBigInteger('gci_part_vendor_id')->nullable()->after('gci_part_id');
                $table->foreign('gci_part_vendor_id')->references('id')->on('gci_part_vendor')->nullOnDelete();
            }
        });

        // location_inventory_adjustments: add gci_part_id
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('location_inventory_adjustments', 'gci_part_id')) {
                $table->unsignedBigInteger('gci_part_id')->nullable()->after('part_id');
                $table->foreign('gci_part_id')->references('id')->on('gci_parts')->nullOnDelete();
            }
        });

        // bin_transfers: add gci_part_id
        Schema::table('bin_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('bin_transfers', 'gci_part_id')) {
                $table->unsignedBigInteger('gci_part_id')->nullable()->after('part_id');
                $table->foreign('gci_part_id')->references('id')->on('gci_parts')->nullOnDelete();
            }
        });

        // purchase_order_items: add gci_part_vendor_id
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'gci_part_vendor_id')) {
                $table->unsignedBigInteger('gci_part_vendor_id')->nullable()->after('vendor_part_id');
                $table->foreign('gci_part_vendor_id')->references('id')->on('gci_part_vendor')->nullOnDelete();
            }
        });

        // ─── 1D: Backfill new columns ───

        // Backfill arrival_items.gci_part_id from parts.gci_part_id
        DB::statement("
            UPDATE arrival_items
            SET gci_part_id = p.gci_part_id
            FROM parts p
            WHERE p.id = arrival_items.part_id
              AND arrival_items.part_id IS NOT NULL
              AND arrival_items.gci_part_id IS NULL
              AND p.gci_part_id IS NOT NULL
        ");

        // Backfill arrival_items.gci_part_vendor_id
        DB::statement("
            UPDATE arrival_items
            SET gci_part_vendor_id = gpv.id
            FROM parts p
            JOIN gci_part_vendor gpv ON gpv.gci_part_id = p.gci_part_id AND gpv.vendor_id = p.vendor_id
            WHERE p.id = arrival_items.part_id
              AND arrival_items.part_id IS NOT NULL
              AND arrival_items.gci_part_vendor_id IS NULL
        ");

        // Backfill location_inventory_adjustments.gci_part_id
        DB::statement("
            UPDATE location_inventory_adjustments
            SET gci_part_id = p.gci_part_id
            FROM parts p
            WHERE p.id = location_inventory_adjustments.part_id
              AND location_inventory_adjustments.part_id IS NOT NULL
              AND location_inventory_adjustments.gci_part_id IS NULL
              AND p.gci_part_id IS NOT NULL
        ");

        // Backfill bin_transfers.gci_part_id
        DB::statement("
            UPDATE bin_transfers
            SET gci_part_id = p.gci_part_id
            FROM parts p
            WHERE p.id = bin_transfers.part_id
              AND bin_transfers.part_id IS NOT NULL
              AND bin_transfers.gci_part_id IS NULL
              AND p.gci_part_id IS NOT NULL
        ");

        // Backfill purchase_order_items.gci_part_vendor_id
        DB::statement("
            UPDATE purchase_order_items
            SET gci_part_vendor_id = gpv.id
            FROM parts p
            JOIN gci_part_vendor gpv ON gpv.gci_part_id = p.gci_part_id AND gpv.vendor_id = p.vendor_id
            WHERE p.id = purchase_order_items.vendor_part_id
              AND purchase_order_items.vendor_part_id IS NOT NULL
              AND purchase_order_items.gci_part_vendor_id IS NULL
        ");
    }

    public function down(): void
    {
        // Remove bridge columns
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'gci_part_vendor_id')) {
                $table->dropForeign(['gci_part_vendor_id']);
                $table->dropColumn('gci_part_vendor_id');
            }
        });

        Schema::table('bin_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('bin_transfers', 'gci_part_id')) {
                $table->dropForeign(['gci_part_id']);
                $table->dropColumn('gci_part_id');
            }
        });

        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('location_inventory_adjustments', 'gci_part_id')) {
                $table->dropForeign(['gci_part_id']);
                $table->dropColumn('gci_part_id');
            }
        });

        Schema::table('arrival_items', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_items', 'gci_part_vendor_id')) {
                $table->dropForeign(['gci_part_vendor_id']);
                $table->dropColumn('gci_part_vendor_id');
            }
            if (Schema::hasColumn('arrival_items', 'gci_part_id')) {
                $table->dropForeign(['gci_part_id']);
                $table->dropColumn('gci_part_id');
            }
        });

        Schema::dropIfExists('gci_part_vendor');
    }
};
