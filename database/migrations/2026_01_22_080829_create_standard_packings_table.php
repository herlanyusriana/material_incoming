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
        Schema::create('standard_packings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gci_part_id')->constrained('gci_parts')->cascadeOnDelete();
            
            // Kolom dari gambar user
            $table->string('delivery_class')->nullable()->default('Main'); // Del_class
            $table->decimal('packing_qty', 10, 2)->default(0);           // Packing qty
            $table->string('uom', 20)->nullable()->default('PCS');       // UOm
            $table->string('trolley_type')->nullable();                  // Trolly type (e.g., 'C')
            
            $table->string('status')->default('active');
            $table->timestamps();

            // Unique constraint: Satu part hanya boleh punya satu standard packing aktif?
            // Atau bisa multiple kalau beda delivery class?
            // Asumsi: 1 Part = 1 Config Utama
            $table->unique(['gci_part_id', 'delivery_class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_packings');
    }
};
