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
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('delivery_plan_id')->constrained('drivers')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->after('driver_id')->constrained('trucks')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable()->after('truck_id');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['truck_id']);
            $table->dropColumn(['driver_id', 'truck_id', 'shipped_at']);
        });
    }
};
