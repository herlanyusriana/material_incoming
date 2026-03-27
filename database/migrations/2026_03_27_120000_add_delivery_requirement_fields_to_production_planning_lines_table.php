<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_planning_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('production_planning_lines', 'delivery_requirement_qty')) {
                $table->decimal('delivery_requirement_qty', 18, 4)->default(0)->after('stock_fg_gci');
            }
            if (!Schema::hasColumn('production_planning_lines', 'delivery_requirement_date_from')) {
                $table->date('delivery_requirement_date_from')->nullable()->after('delivery_requirement_qty');
            }
            if (!Schema::hasColumn('production_planning_lines', 'delivery_requirement_date_to')) {
                $table->date('delivery_requirement_date_to')->nullable()->after('delivery_requirement_date_from');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_planning_lines', function (Blueprint $table) {
            $columns = [];
            foreach (['delivery_requirement_qty', 'delivery_requirement_date_from', 'delivery_requirement_date_to'] as $column) {
                if (Schema::hasColumn('production_planning_lines', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
