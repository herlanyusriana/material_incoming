<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('production_orders', 'active_operator_name')) {
                $column = $table->string('active_operator_name')->nullable();
                if (Schema::hasColumn('production_orders', 'machine_name')) {
                    $column->after('machine_name');
                }
            }

            if (!Schema::hasColumn('production_orders', 'active_operator_started_at')) {
                $table->timestamp('active_operator_started_at')->nullable()->after('active_operator_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'active_operator_started_at')) {
                $table->dropColumn('active_operator_started_at');
            }

            if (Schema::hasColumn('production_orders', 'active_operator_name')) {
                $table->dropColumn('active_operator_name');
            }
        });
    }
};
