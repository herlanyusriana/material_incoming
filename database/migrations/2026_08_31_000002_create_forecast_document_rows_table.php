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
        if (!Schema::hasTable('forecast_document_rows')) {
            Schema::create('forecast_document_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('forecast_document_id')->constrained('forecast_documents')->onDelete('cascade');
                $table->string('customer_part_no', 191)->nullable();
                $table->string('customer_part_name', 255)->nullable();
                $table->unsignedBigInteger('gci_part_id')->nullable();
                $table->string('mapping_status', 20)->default('unmapped'); // mapped | unmapped
                $table->string('row_no', 20)->nullable(); // original Excel row number
                $table->json('quantities')->nullable(); // { "YYYY-MM": qty }
                $table->timestamps();

                $table->index('forecast_document_id');
                $table->index('gci_part_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_document_rows');
    }
};
