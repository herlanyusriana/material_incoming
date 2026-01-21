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
        // Create arrivals table
        Schema::create('arrivals', function (Blueprint $table) {
            $table->id();
            $table->string('arrival_no')->unique();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->string('status')->default('pending');
            $table->date('eta_date')->nullable();
            $table->date('ata_date')->nullable();
            $table->string('country')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Create arrival_items table
        Schema::create('arrival_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained('arrivals')->onDelete('cascade');
            $table->foreignId('part_id')->nullable()->constrained('parts')->onDelete('set null');
            $table->string('material_group')->nullable();
            $table->decimal('qty_goods', 20, 3)->default(0);
            $table->string('unit_goods', 20)->nullable();
            $table->decimal('qty_bundle', 20, 3)->nullable();
            $table->string('unit_bundle', 20)->nullable();
            $table->decimal('weight_nett', 20, 3)->nullable();
            $table->string('unit_weight', 20)->nullable();
            $table->decimal('weight_gross', 20, 3)->nullable();
            $table->decimal('price_unit', 20, 3)->nullable();
            $table->string('size', 100)->nullable();
            $table->timestamps();
        });

        // Create arrival_containers table
        Schema::create('arrival_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained('arrivals')->onDelete('cascade');
            $table->string('container_no')->nullable();
            $table->string('seal_no')->nullable();
            $table->timestamps();
        });

        // Create arrival_container_inspections table
        Schema::create('arrival_container_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_container_id')->constrained('arrival_containers')->onDelete('cascade');
            $table->string('seal_condition')->nullable();
            $table->string('container_condition')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_seal')->nullable();
            $table->string('photo_inside')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_container_inspections');
        Schema::dropIfExists('arrival_containers');
        Schema::dropIfExists('arrival_items');
        Schema::dropIfExists('arrivals');
    }
};
