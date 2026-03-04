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
        Schema::table('machines', function (Blueprint $table) {
            $table->decimal('setup_time_minutes', 8, 2)->default(0)->after('cycle_time_unit');
            $table->decimal('available_hours_per_shift', 4, 2)->default(7)->after('setup_time_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['setup_time_minutes', 'available_hours_per_shift']);
        });
    }
};
