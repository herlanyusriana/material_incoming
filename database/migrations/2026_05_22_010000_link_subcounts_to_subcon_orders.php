<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcount_batches')) {
            return;
        }

        Schema::table('subcount_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('subcount_batches', 'subcon_order_id')) {
                $table->foreignId('subcon_order_id')
                    ->nullable()
                    ->after('external_id')
                    ->constrained('subcon_orders')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('subcount_batches', 'subcon_order_no')) {
                $table->string('subcon_order_no', 100)->nullable()->after('subcon_order_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subcount_batches')) {
            return;
        }

        Schema::table('subcount_batches', function (Blueprint $table) {
            if (Schema::hasColumn('subcount_batches', 'subcon_order_id')) {
                $table->dropConstrainedForeignId('subcon_order_id');
            }

            if (Schema::hasColumn('subcount_batches', 'subcon_order_no')) {
                $table->dropColumn('subcon_order_no');
            }
        });
    }
};
