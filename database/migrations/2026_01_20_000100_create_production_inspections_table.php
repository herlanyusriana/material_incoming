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
        Schema::create('production_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders');
            $table->enum('type', ['first_article', 'in_process', 'final']);
            $table->enum('status', ['pending', 'pass', 'fail'])->default('pending');
            $table->json('details')->nullable(); // For storing measurements / checklist data
            $table->foreignId('inspector_id')->nullable()->constrained('users');
            $table->dateTime('inspected_at')->nullable();
            $table->json('photo_evidence')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_inspections');
    }
};
