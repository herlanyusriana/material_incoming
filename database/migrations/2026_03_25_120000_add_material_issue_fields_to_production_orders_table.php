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
            if (!Schema::hasColumn('production_orders', 'material_issue_lines')) {
                $table->json('material_issue_lines')->nullable()->after('material_requested_by');
            }
            if (!Schema::hasColumn('production_orders', 'material_issued_at')) {
                $table->timestamp('material_issued_at')->nullable()->after('material_issue_lines');
            }
            if (!Schema::hasColumn('production_orders', 'material_issued_by')) {
                $table->foreignId('material_issued_by')->nullable()->after('material_issued_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'material_issued_by')) {
                $table->dropConstrainedForeignId('material_issued_by');
            }
            foreach (['material_issued_at', 'material_issue_lines'] as $column) {
                if (Schema::hasColumn('production_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
