<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('location_inventory_adjustments', 'event_type')) {
                $table->string('event_type', 50)->nullable()->after('source_reference');
                $table->index('event_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('location_inventory_adjustments', 'event_type')) {
                $table->dropIndex(['event_type']);
                $table->dropColumn('event_type');
            }
        });
    }
};
