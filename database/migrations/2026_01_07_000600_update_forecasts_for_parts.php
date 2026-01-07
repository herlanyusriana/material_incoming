<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forecasts', function (Blueprint $table) {
            if (!Schema::hasColumn('forecasts', 'part_id')) {
                $table->foreignId('part_id')->nullable()->after('id')->constrained('gci_parts')->nullOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('forecasts', 'minggu')) {
                $table->string('minggu', 8)->nullable()->after('period');
            }
            if (!Schema::hasColumn('forecasts', 'planning_qty')) {
                $table->decimal('planning_qty', 15, 3)->default(0)->after('qty');
            }
            if (!Schema::hasColumn('forecasts', 'po_qty')) {
                $table->decimal('po_qty', 15, 3)->default(0)->after('planning_qty');
            }
            if (!Schema::hasColumn('forecasts', 'source')) {
                $table->string('source', 20)->nullable()->after('po_qty');
            }
        });

        Schema::table('forecasts', function (Blueprint $table) {
            if (Schema::hasColumn('forecasts', 'part_id') && Schema::hasColumn('forecasts', 'minggu')) {
                $table->unique(['part_id', 'minggu'], 'forecasts_part_minggu_unique');
                $table->index('minggu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('forecasts', function (Blueprint $table) {
            if (Schema::hasColumn('forecasts', 'part_id') && Schema::hasColumn('forecasts', 'minggu')) {
                $table->dropUnique('forecasts_part_minggu_unique');
                $table->dropIndex(['minggu']);
            }
            if (Schema::hasColumn('forecasts', 'part_id')) {
                $table->dropConstrainedForeignId('part_id');
            }
            if (Schema::hasColumn('forecasts', 'minggu')) {
                $table->dropColumn('minggu');
            }
            if (Schema::hasColumn('forecasts', 'planning_qty')) {
                $table->dropColumn('planning_qty');
            }
            if (Schema::hasColumn('forecasts', 'po_qty')) {
                $table->dropColumn('po_qty');
            }
            if (Schema::hasColumn('forecasts', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
