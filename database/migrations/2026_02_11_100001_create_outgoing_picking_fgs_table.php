<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outgoing_picking_fgs', function (Blueprint $table) {
            $table->id();
            $table->date('delivery_date');
            $table->foreignId('gci_part_id')->constrained('gci_parts');
            $table->integer('qty_plan')->default(0);
            $table->integer('qty_picked')->default(0);
            $table->string('status', 20)->default('pending'); // pending, picking, completed
            $table->string('pick_location')->nullable();
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picked_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['delivery_date', 'gci_part_id']);
            $table->index(['delivery_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_picking_fgs');
    }
};
