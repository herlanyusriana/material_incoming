<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mps_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('action', ['generate', 'clear'])->default('generate');
            $table->integer('parts_count')->default(0);
            $table->string('weeks_generated')->nullable(); // e.g., "2026-W03, 2026-W04"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('mrp_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->nullable()->constrained('mrp_runs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('action', ['generate', 'clear'])->default('generate');
            $table->integer('parts_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('forecast_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('action', ['generate', 'clear'])->default('generate');
            $table->integer('parts_count')->default(0);
            $table->string('weeks_generated')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_histories');
        Schema::dropIfExists('mrp_histories');
        Schema::dropIfExists('mps_histories');
    }
};
