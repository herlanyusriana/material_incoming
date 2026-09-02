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
        if (!Schema::hasTable('forecast_documents')) {
            Schema::create('forecast_documents', function (Blueprint $table) {
                $table->id();
                $table->string('document_no', 50)->nullable()->index();
                $table->string('source', 30)->default('lG_plan'); // lG_plan | customer_po | manual
                $table->string('period_start', 7)->nullable(); // YYYY-MM
                $table->string('period_end', 7)->nullable();   // YYYY-MM
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->dateTime('uploaded_at')->nullable();
                $table->string('status', 20)->default('preview'); // preview | committed | cancelled
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('mapped_rows')->default(0);
                $table->unsignedInteger('unmapped_rows')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index(['period_start', 'period_end']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_documents');
    }
};
