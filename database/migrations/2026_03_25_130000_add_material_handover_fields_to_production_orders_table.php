<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('production_orders', 'material_handed_over_at')) {
                $table->timestamp('material_handed_over_at')->nullable()->after('material_issued_by');
            }
            if (!Schema::hasColumn('production_orders', 'material_handed_over_by')) {
                $table->foreignId('material_handed_over_by')->nullable()->after('material_handed_over_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('production_orders', 'material_handover_notes')) {
                $table->text('material_handover_notes')->nullable()->after('material_handed_over_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'material_handed_over_by')) {
                $table->dropConstrainedForeignId('material_handed_over_by');
            }
            foreach (['material_handover_notes', 'material_handed_over_at'] as $column) {
                if (Schema::hasColumn('production_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
