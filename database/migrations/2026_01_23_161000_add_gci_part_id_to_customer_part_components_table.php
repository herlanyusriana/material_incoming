<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('customer_part_components')) {
            return;
        }

        if (Schema::hasColumn('customer_part_components', 'gci_part_id')) {
            return;
        }

        Schema::table('customer_part_components', function (Blueprint $table) {
            $table->unsignedBigInteger('gci_part_id')->nullable()->after('customer_part_id');
            $table->index('gci_part_id');
        });

        // Backfill from legacy column names if present.
        if (Schema::hasColumn('customer_part_components', 'part_id')) {
            DB::table('customer_part_components')
                ->whereNull('gci_part_id')
                ->update(['gci_part_id' => DB::raw('part_id')]);
        } elseif (Schema::hasColumn('customer_part_components', 'component_part_id')) {
            DB::table('customer_part_components')
                ->whereNull('gci_part_id')
                ->update(['gci_part_id' => DB::raw('component_part_id')]);
        }

        // Try to add FK + unique when safe; don't fail the migration if legacy data isn't clean.
        try {
            if (Schema::hasTable('gci_parts')) {
                $missing = (int) (DB::table('customer_part_components as cpc')
                    ->leftJoin('gci_parts as gp', 'gp.id', '=', 'cpc.gci_part_id')
                    ->whereNotNull('cpc.gci_part_id')
                    ->whereNull('gp.id')
                    ->count());

                if ($missing === 0) {
                    Schema::table('customer_part_components', function (Blueprint $table) {
                        $table->foreign('gci_part_id')
                            ->references('id')
                            ->on('gci_parts')
                            ->onDelete('cascade');
                    });
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $duplicates = DB::table('customer_part_components')
                ->select('customer_part_id', 'gci_part_id', DB::raw('COUNT(*) as c'))
                ->whereNotNull('gci_part_id')
                ->groupBy('customer_part_id', 'gci_part_id')
                ->having('c', '>', 1)
                ->limit(1)
                ->exists();

            if (!$duplicates) {
                Schema::table('customer_part_components', function (Blueprint $table) {
                    $table->unique(['customer_part_id', 'gci_part_id']);
                });
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_part_components') || !Schema::hasColumn('customer_part_components', 'gci_part_id')) {
            return;
        }

        // Best-effort rollback; ignore constraint name variations.
        try {
            Schema::table('customer_part_components', function (Blueprint $table) {
                try {
                    $table->dropForeign(['gci_part_id']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropUnique(['customer_part_id', 'gci_part_id']);
                } catch (\Throwable $e) {
                }
                $table->dropIndex(['gci_part_id']);
                $table->dropColumn('gci_part_id');
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }
};

