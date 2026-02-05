<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outgoing_daily_plan_rows', function (Blueprint $table) {
            if (!Schema::hasColumn('outgoing_daily_plan_rows', 'customer_part_id')) {
                $table->foreignId('customer_part_id')->nullable()->after('gci_part_id')->constrained('customer_parts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_daily_plan_rows', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_daily_plan_rows', 'customer_part_id')) {
                $table->dropForeign(['customer_part_id']);
                $table->dropColumn('customer_part_id');
            }
        });
    }
};
