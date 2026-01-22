<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TRUCKS
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_no')->unique(); // e.g., B 1234 XX
            $table->string('type')->nullable(); // e.g., Box Truck, Wingbox
            $table->string('capacity')->nullable(); // e.g., 5 Ton
            $table->string('status')->default('available'); // available, in-use, maintenance
            $table->timestamps();
        });

        // DRIVERS
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('license_type')->nullable(); // SIM B1, B2
            $table->string('status')->default('available'); // available, on-delivery, off
            $table->timestamps();
        });

        // DELIVERY PLANS (Trips)
        Schema::create('delivery_plans', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date')->index();
            $table->integer('sequence')->default(1);
            
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            
            $table->string('status')->default('scheduled'); // scheduled, in-progress, completed, unassigned
            $table->time('estimated_departure')->nullable();
            $table->time('estimated_return')->nullable();
            
            $table->timestamps();
        });

        // DELIVERY STOPS (Destinations within a Plan)
        Schema::create('delivery_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('delivery_plans')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            $table->integer('sequence')->default(1);
            $table->time('estimated_arrival_time')->nullable();
            $table->string('status')->default('pending'); // pending, completed
            
            $table->timestamps();
        });

        // Update Delivery Notes to link to Stops
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreignId('delivery_stop_id')->nullable()->constrained('delivery_stops')->nullOnDelete();
            // We can also have plan_id for easier querying, but stop_id is more precise
            $table->foreignId('delivery_plan_id')->nullable()->constrained('delivery_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['delivery_stop_id']);
            $table->dropForeign(['delivery_plan_id']);
            $table->dropColumn(['delivery_stop_id', 'delivery_plan_id']);
        });

        Schema::dropIfExists('delivery_stops');
        Schema::dropIfExists('delivery_plans');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('trucks');
    }
};
