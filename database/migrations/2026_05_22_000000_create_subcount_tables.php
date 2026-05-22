<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcount_batches', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->string('subcount_no')->unique();
            $table->timestamp('created_at_mobile')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('title');
            $table->string('part_info')->nullable();
            $table->string('operator_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_net_weight_kg', 20, 4)->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('subcount_packaging_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcount_batch_id')->constrained('subcount_batches')->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->timestamp('created_at_mobile')->nullable();
            $table->string('packaging_id');
            $table->string('packaging_type')->nullable();
            $table->decimal('packaging_weight_kg', 20, 4)->default(0);
            $table->decimal('gross_weight_kg', 20, 4)->default(0);
            $table->decimal('net_item_weight_kg', 20, 4)->default(0);
            $table->text('description')->nullable();
            $table->string('packaging_photo_path')->nullable();
            $table->string('gross_photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcount_packaging_records');
        Schema::dropIfExists('subcount_batches');
    }
};
