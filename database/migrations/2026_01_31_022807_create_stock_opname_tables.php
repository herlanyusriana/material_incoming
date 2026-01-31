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
        Schema::create('stock_opname_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_no')->unique();
            $table->string('name');
            $table->enum('status', ['OPEN', 'CLOSED', 'ADJUSTED'])->default('OPEN');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('stock_opname_sessions')->onDelete('cascade');
            $table->string('location_code');
            $table->foreignId('gci_part_id')->constrained('gci_parts');
            $table->decimal('system_qty', 15, 4)->default(0);
            $table->decimal('counted_qty', 15, 4)->default(0);
            $table->decimal('difference', 15, 4)->virtualAs('counted_qty - system_qty');
            $table->foreignId('counted_by')->nullable()->constrained('users');
            $table->timestamp('counted_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            // Index for faster lookups during scanning
            $table->index(['session_id', 'location_code', 'gci_part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_sessions');
    }
};
