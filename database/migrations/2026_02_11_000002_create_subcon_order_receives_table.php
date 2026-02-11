<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_order_receives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcon_order_id')->constrained('subcon_orders')->cascadeOnDelete();
            $table->decimal('qty_good', 20, 4);
            $table->decimal('qty_rejected', 20, 4)->default(0);
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_order_receives');
    }
};
