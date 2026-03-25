<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('production_orders', 'fg_handed_over_to_wh_at')) {
                $table->timestamp('fg_handed_over_to_wh_at')->nullable()->after('fg_supply_notes');
            }
            if (!Schema::hasColumn('production_orders', 'fg_handed_over_to_wh_by')) {
                $table->foreignId('fg_handed_over_to_wh_by')->nullable()->after('fg_handed_over_to_wh_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('production_orders', 'fg_handover_notes')) {
                $table->text('fg_handover_notes')->nullable()->after('fg_handed_over_to_wh_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'fg_handed_over_to_wh_by')) {
                $table->dropConstrainedForeignId('fg_handed_over_to_wh_by');
            }
            foreach (['fg_handover_notes', 'fg_handed_over_to_wh_at'] as $column) {
                if (Schema::hasColumn('production_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
