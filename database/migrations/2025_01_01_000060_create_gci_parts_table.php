<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gci_parts')) {
            Schema::create('gci_parts', function (Blueprint $table) {
                $table->id();
                $table->string('part_no', 100)->unique();
                $table->string('part_name', 255)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gci_parts');
    }
};

