<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('arrival_containers')) {
            return;
        }

        Schema::create('arrival_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained('arrivals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('container_no', 50);
            $table->string('seal_code', 100)->nullable();
            $table->timestamps();

            $table->unique(['arrival_id', 'container_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_containers');
    }
};

