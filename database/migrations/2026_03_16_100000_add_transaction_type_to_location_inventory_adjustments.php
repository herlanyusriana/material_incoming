<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            $table->string('transaction_type', 50)->nullable()->after('action_type')
                ->comment('RECEIVE, PRODUCTION_OUTPUT, BACKFLUSH, PICKING, DELIVERY, TRANSFER, ADJUSTMENT, IMPORT, SYNC');
            $table->string('source_reference', 255)->nullable()->after('transaction_type')
                ->comment('e.g. PO#123, DN#456, PROD#789');

            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
            $table->dropColumn(['transaction_type', 'source_reference']);
        });
    }
};