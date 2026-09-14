<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generic_part_id')->constrained('gci_parts')->onDelete('cascade');
            $table->foreignId('substitute_part_id')->constrained('gci_parts')->onDelete('cascade');
            $table->foreignId('vendor_part_id')->nullable()->constrained('vendor_parts')->nullOnDelete();
            $table->decimal('ratio', 10, 4)->default(1);
            $table->integer('priority')->default(1);
            $table->string('status')->default('active');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Exclusive: one substitute part should only belong to ONE generic
            $table->unique(['substitute_part_id'], 'material_sub_sub_unique');

            // No duplicate generic+sub+vendor combo
            $table->unique(
                ['generic_part_id', 'substitute_part_id', 'vendor_part_id'],
                'material_sub_generic_vendor_unique'
            );

            });

        // No self-substitute: hard CHECK constraint (MySQL 8 enforced).
        DB::statement(
            'ALTER TABLE material_substitutes '
            . 'ADD CONSTRAINT material_sub_no_self_check '
            . 'CHECK (generic_part_id <> substitute_part_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('material_substitutes');
    }
};