<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('delivery_notes', 'driver_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->unsignedBigInteger('driver_id')->nullable()->after('truck_id');
                $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_notes', 'driver_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            });
        }
    }
};