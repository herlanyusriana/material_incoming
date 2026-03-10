<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_gci_downtimes', function (Blueprint $table) {
            $table->string('refill_part_no')->nullable()->after('notes');
            $table->string('refill_part_name')->nullable()->after('refill_part_no');
            $table->decimal('refill_qty', 10, 2)->nullable()->after('refill_part_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_gci_downtimes', function (Blueprint $table) {
            $table->dropColumn(['refill_part_no', 'refill_part_name', 'refill_qty']);
        });
    }
};
