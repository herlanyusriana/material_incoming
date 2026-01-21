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
        // Create parts table
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_no')->unique();
            $table->string('part_name_gci')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->string('status')->default('active');
            $table->string('uom')->nullable();
            $table->decimal('price', 20, 3)->nullable();
            $table->string('hs_code')->nullable();
            $table->boolean('quality_inspection')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        // Create inventories table
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->unique()->constrained('parts')->onDelete('cascade');
            $table->decimal('on_hand', 20, 3)->default(0);
            $table->decimal('on_order', 20, 3)->default(0);
            $table->decimal('allocated', 20, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('parts');
    }
};
