<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique('part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
