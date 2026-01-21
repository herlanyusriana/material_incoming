<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrival_container_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_container_id')->constrained('arrival_containers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('status', 20); // ok | damage
            $table->string('seal_code', 100)->nullable();
            $table->text('notes')->nullable();

            $table->json('issues_left')->nullable();
            $table->json('issues_right')->nullable();
            $table->json('issues_front')->nullable();
            $table->json('issues_back')->nullable();

            $table->string('photo_left')->nullable();
            $table->string('photo_right')->nullable();
            $table->string('photo_front')->nullable();
            $table->string('photo_back')->nullable();
            $table->string('photo_inside')->nullable();

            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique('arrival_container_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_container_inspections');
    }
};

