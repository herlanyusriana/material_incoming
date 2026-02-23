<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // WO ↔ SO pivot table
        Schema::create('production_order_arrivals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('arrival_id');
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('arrival_id')->references('id')->on('arrivals')->onDelete('cascade');
            $table->unique(['production_order_id', 'arrival_id']);
        });

        // DO ↔ WO pivot table
        Schema::create('delivery_note_production_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_note_id');
            $table->unsignedBigInteger('production_order_id');
            $table->timestamps();

            $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->onDelete('cascade');
            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->unique(['delivery_note_id', 'production_order_id']);
        });

        // Drop old string columns
        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'related_so')) {
                $table->dropColumn('related_so');
            }
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_notes', 'related_wo')) {
                $table->dropColumn('related_wo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('related_so')->nullable()->after('transaction_no');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('related_wo')->nullable()->after('transaction_no');
        });

        Schema::dropIfExists('delivery_note_production_orders');
        Schema::dropIfExists('production_order_arrivals');
    }
};
