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
            if (!Schema::hasColumn('production_orders', 'material_request_lines')) {
                $table->json('material_request_lines')->nullable()->after('reserved_materials');
            }
            if (!Schema::hasColumn('production_orders', 'material_requested_at')) {
                $table->timestamp('material_requested_at')->nullable()->after('material_request_lines');
            }
            if (!Schema::hasColumn('production_orders', 'material_requested_by')) {
                $table->foreignId('material_requested_by')->nullable()->after('material_requested_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'material_requested_by')) {
                $table->dropConstrainedForeignId('material_requested_by');
            }
            foreach (['material_requested_at', 'material_request_lines'] as $column) {
                if (Schema::hasColumn('production_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
