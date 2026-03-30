<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_planning_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('production_planning_lines', 'shift_1_qty')) {
                $table->decimal('shift_1_qty', 18, 4)->default(0)->after('plan_qty');
            }
            if (!Schema::hasColumn('production_planning_lines', 'shift_2_qty')) {
                $table->decimal('shift_2_qty', 18, 4)->default(0)->after('shift_1_qty');
            }
            if (!Schema::hasColumn('production_planning_lines', 'shift_3_qty')) {
                $table->decimal('shift_3_qty', 18, 4)->default(0)->after('shift_2_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_planning_lines', function (Blueprint $table) {
            foreach (['shift_1_qty', 'shift_2_qty', 'shift_3_qty'] as $column) {
                if (Schema::hasColumn('production_planning_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
