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

        if (Schema::hasColumn('customer_part_components', 'qty_per_unit')) {
            return;
        }

        Schema::table('customer_part_components', function (Blueprint $table) {
            $table->decimal('qty_per_unit', 20, 4)->default(1)->after('gci_part_id');
        });

        // Backfill from legacy column names if present.
        $legacyColumns = ['qty', 'usage_qty', 'usage', 'qty_per', 'qty_perunit'];
        foreach ($legacyColumns as $legacyColumn) {
            if (!Schema::hasColumn('customer_part_components', $legacyColumn)) {
                continue;
            }

            DB::table('customer_part_components')
                ->update(['qty_per_unit' => DB::raw($legacyColumn)]);

            break;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_part_components') || !Schema::hasColumn('customer_part_components', 'qty_per_unit')) {
            return;
        }

        Schema::table('customer_part_components', function (Blueprint $table) {
            $table->dropColumn('qty_per_unit');
        });
    }
};

