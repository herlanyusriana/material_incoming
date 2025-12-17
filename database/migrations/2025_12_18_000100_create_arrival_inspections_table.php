<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrival_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained('arrivals')->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['ok', 'damage'])->default('ok');
            $table->text('notes')->nullable();
            $table->string('photo_left')->nullable();
            $table->string('photo_right')->nullable();
            $table->string('photo_front')->nullable();
            $table->string('photo_back')->nullable();
            $table->json('issues_left')->nullable();
            $table->json('issues_right')->nullable();
            $table->json('issues_front')->nullable();
            $table->json('issues_back')->nullable();
            $table->timestamps();

            $table->unique('arrival_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_inspections');
    }
};
