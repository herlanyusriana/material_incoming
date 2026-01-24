<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('production_orders', 'process_name')) {
                $table->string('process_name', 255)->nullable()->after('gci_part_id');
            }
            if (!Schema::hasColumn('production_orders', 'machine_name')) {
                $table->string('machine_name', 255)->nullable()->after('process_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'machine_name')) {
                $table->dropColumn('machine_name');
            }
            if (Schema::hasColumn('production_orders', 'process_name')) {
                $table->dropColumn('process_name');
            }
        });
    }
};

