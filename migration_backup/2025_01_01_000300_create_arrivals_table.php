<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrivals', function (Blueprint $table) {
            $table->id();
            $table->string('arrival_no')->unique();
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->foreignId('vendor_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('vessel')->nullable();
            $table->string('trucking_company')->nullable();
            $table->date('ETD')->nullable();
            $table->string('bill_of_lading')->nullable();
            $table->string('hs_code')->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('currency')->default('USD');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrivals');
    }
};
