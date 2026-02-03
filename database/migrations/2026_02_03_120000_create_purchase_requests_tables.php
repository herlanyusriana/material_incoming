<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique();
            $table->foreignId('requester_id')->constrained('users');
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Converted, Cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->foreignId('part_id')->constrained('gci_parts'); // Linking to gci_parts initially as per MrpPurchasePlan
            $table->decimal('qty', 20, 4);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->date('required_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
