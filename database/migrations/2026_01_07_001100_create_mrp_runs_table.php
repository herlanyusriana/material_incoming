<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_runs', function (Blueprint $table) {
            $table->id();
            $table->string('minggu', 8);
            $table->string('status', 20)->default('completed');
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('run_at')->nullable();
            $table->timestamps();

            $table->index('minggu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_runs');
    }
};
