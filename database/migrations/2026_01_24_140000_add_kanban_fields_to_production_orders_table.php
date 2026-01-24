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
            if (!Schema::hasColumn('production_orders', 'die_name')) {
                $table->string('die_name', 255)->nullable()->after('machine_name');
            }
            if (!Schema::hasColumn('production_orders', 'released_at')) {
                $table->dateTime('released_at')->nullable()->after('workflow_stage');
            }
            if (!Schema::hasColumn('production_orders', 'released_by')) {
                $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete()->after('released_at');
            }
            if (!Schema::hasColumn('production_orders', 'qty_ng')) {
                $table->decimal('qty_ng', 18, 4)->default(0)->after('qty_actual');
            }
            if (!Schema::hasColumn('production_orders', 'kanban_updated_at')) {
                $table->dateTime('kanban_updated_at')->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('production_orders', 'kanban_updated_by')) {
                $table->foreignId('kanban_updated_by')->nullable()->constrained('users')->nullOnDelete()->after('kanban_updated_at');
            }
            if (!Schema::hasColumn('production_orders', 'dies_adjustment_notes')) {
                $table->text('dies_adjustment_notes')->nullable()->after('kanban_updated_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            foreach (['dies_adjustment_notes', 'kanban_updated_by', 'kanban_updated_at', 'qty_ng', 'released_by', 'released_at', 'die_name'] as $col) {
                if (Schema::hasColumn('production_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

