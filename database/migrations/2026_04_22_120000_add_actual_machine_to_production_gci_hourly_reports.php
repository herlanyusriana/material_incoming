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
            if (!Schema::hasColumn('production_gci_hourly_reports', 'machine_id')) {
                $table->foreignId('machine_id')
                    ->nullable()
                    ->after('production_order_id')
                    ->constrained('machines')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('production_gci_hourly_reports', 'machine_name')) {
                $table->string('machine_name')->nullable()->after('machine_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_gci_hourly_reports')) {
            return;
        }

        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            if (Schema::hasColumn('production_gci_hourly_reports', 'machine_id')) {
                $table->dropConstrainedForeignId('machine_id');
            }

            if (Schema::hasColumn('production_gci_hourly_reports', 'machine_name')) {
                $table->dropColumn('machine_name');
            }
        });
    }
};
