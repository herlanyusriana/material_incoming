<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_gci_hourly_reports')) {
            return;
        }

        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('production_gci_hourly_reports', 'ng_scrap')) {
                $table->integer('ng_scrap')->default(0)->after('ng_reason');
            }
            if (!Schema::hasColumn('production_gci_hourly_reports', 'ng_rework')) {
                $table->integer('ng_rework')->default(0)->after('ng_scrap');
            }
            if (!Schema::hasColumn('production_gci_hourly_reports', 'ng_hold')) {
                $table->integer('ng_hold')->default(0)->after('ng_rework');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_gci_hourly_reports')) {
            return;
        }

        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            foreach (['ng_hold', 'ng_rework', 'ng_scrap'] as $column) {
                if (Schema::hasColumn('production_gci_hourly_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
