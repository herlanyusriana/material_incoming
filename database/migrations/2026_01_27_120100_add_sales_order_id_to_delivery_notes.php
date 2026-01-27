<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_notes')) {
            return;
        }

        Schema::table('delivery_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_notes', 'sales_order_id')) {
                $table->foreignId('sales_order_id')->nullable()->after('id')->constrained('sales_orders')->nullOnDelete();
                $table->index(['sales_order_id']);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('delivery_notes')) {
            return;
        }

        Schema::table('delivery_notes', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_notes', 'sales_order_id')) {
                try {
                    $table->dropForeign(['sales_order_id']);
                } catch (\Throwable $e) {
                }
                $table->dropIndex(['sales_order_id']);
                $table->dropColumn('sales_order_id');
            }
        });
    }
};

