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
        if (!Schema::hasTable('receives')) {
            Schema::create('receives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('arrival_item_id')->constrained()->onDelete('cascade');
                $table->string('tag')->nullable();
                $table->integer('qty');
                $table->dateTime('ata_date');
                $table->string('qc_status'); // pass, fail, hold
                $table->decimal('weight', 8, 2)->nullable();
                $table->string('jo_po_number')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receives');
    }
};
