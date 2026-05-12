<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcon_orders')) {
            return;
        }

        Schema::table('subcon_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('subcon_orders', 'production_order_id')) {
                $table->foreignId('production_order_id')
                    ->nullable()
                    ->after('bom_item_id')
                    ->constrained('production_orders')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('subcon_orders', 'production_order_number')) {
                $table->string('production_order_number', 100)->nullable()->after('production_order_id');
            }

            if (!Schema::hasColumn('subcon_orders', 'source_process_name')) {
                $table->string('source_process_name', 255)->nullable()->after('process_type');
            }

            if (!Schema::hasColumn('subcon_orders', 'target_process_name')) {
                $table->string('target_process_name', 255)->nullable()->after('source_process_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subcon_orders')) {
            return;
        }

        Schema::table('subcon_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subcon_orders', 'production_order_id')) {
                $table->dropConstrainedForeignId('production_order_id');
            }

            foreach (['production_order_number', 'source_process_name', 'target_process_name'] as $column) {
                if (Schema::hasColumn('subcon_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
