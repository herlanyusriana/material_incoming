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
        if (!Schema::hasTable('customer_planning_imports')) {
            Schema::create('customer_planning_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->string('file_path')->nullable();
                $table->string('original_filename')->nullable();
                $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
                $table->foreignId('imported_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('imported_at')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('rows_imported')->default(0);
                $table->timestamps();

                // Indexes
                $table->index('customer_id');
                $table->index('status');
                $table->index('imported_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_planning_imports');
    }
};
