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
            if (!Schema::hasColumn('production_orders', 'fg_supplied_to_wh_at')) {
                $table->timestamp('fg_supplied_to_wh_at')->nullable()->after('material_handover_notes');
            }
            if (!Schema::hasColumn('production_orders', 'fg_supplied_to_wh_by')) {
                $table->foreignId('fg_supplied_to_wh_by')->nullable()->after('fg_supplied_to_wh_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('production_orders', 'fg_supply_location_code')) {
                $table->string('fg_supply_location_code', 50)->nullable()->after('fg_supplied_to_wh_by');
            }
            if (!Schema::hasColumn('production_orders', 'fg_supply_qty')) {
                $table->decimal('fg_supply_qty', 18, 4)->nullable()->after('fg_supply_location_code');
            }
            if (!Schema::hasColumn('production_orders', 'fg_supply_notes')) {
                $table->text('fg_supply_notes')->nullable()->after('fg_supply_qty');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('production_orders')) {
            return;
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'fg_supplied_to_wh_by')) {
                $table->dropConstrainedForeignId('fg_supplied_to_wh_by');
            }
            foreach (['fg_supply_notes', 'fg_supply_qty', 'fg_supply_location_code', 'fg_supplied_to_wh_at'] as $column) {
                if (Schema::hasColumn('production_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
