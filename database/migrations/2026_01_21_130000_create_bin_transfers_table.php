<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bin_transfers')) {
            Schema::create('bin_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
                $table->string('from_location_code');
                $table->string('to_location_code');
                $table->decimal('qty', 20, 4);
                $table->date('transfer_date');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->string('status')->default('completed'); // 'pending', 'completed', 'cancelled'
                $table->timestamps();
                $table->softDeletes();

                // Indexes for performance
                $table->index('part_id');
                $table->index('from_location_code');
                $table->index('to_location_code');
                $table->index('transfer_date');
                $table->index('status');
                $table->index('created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bin_transfers');
    }
};
