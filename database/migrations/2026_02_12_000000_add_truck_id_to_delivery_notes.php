<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('delivery_notes', 'truck_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->unsignedBigInteger('truck_id')->nullable()->after('customer_id');
                $table->foreign('truck_id')->references('id')->on('trucking_companies')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_notes', 'truck_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->dropForeign(['truck_id']);
                $table->dropColumn('truck_id');
            });
        }
    }
};