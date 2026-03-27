<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('location_inventory_adjustments', 'source_receive_id')) {
                $table->unsignedBigInteger('source_receive_id')->nullable()->after('batch_no');
                $table->index('source_receive_id', 'lia_source_receive_id_idx');
            }

            if (!Schema::hasColumn('location_inventory_adjustments', 'source_arrival_id')) {
                $table->unsignedBigInteger('source_arrival_id')->nullable()->after('source_receive_id');
                $table->index('source_arrival_id', 'lia_source_arrival_id_idx');
            }

            if (!Schema::hasColumn('location_inventory_adjustments', 'source_invoice_no')) {
                $table->string('source_invoice_no', 255)->nullable()->after('source_arrival_id');
                $table->index('source_invoice_no', 'lia_source_invoice_no_idx');
            }

            if (!Schema::hasColumn('location_inventory_adjustments', 'source_delivery_note_no')) {
                $table->string('source_delivery_note_no', 255)->nullable()->after('source_invoice_no');
            }

            if (!Schema::hasColumn('location_inventory_adjustments', 'source_tag')) {
                $table->string('source_tag', 255)->nullable()->after('source_delivery_note_no');
                $table->index('source_tag', 'lia_source_tag_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('location_inventory_adjustments', 'source_tag')) {
                $table->dropIndex('lia_source_tag_idx');
                $table->dropColumn('source_tag');
            }

            if (Schema::hasColumn('location_inventory_adjustments', 'source_delivery_note_no')) {
                $table->dropColumn('source_delivery_note_no');
            }

            if (Schema::hasColumn('location_inventory_adjustments', 'source_invoice_no')) {
                $table->dropIndex('lia_source_invoice_no_idx');
                $table->dropColumn('source_invoice_no');
            }

            if (Schema::hasColumn('location_inventory_adjustments', 'source_arrival_id')) {
                $table->dropIndex('lia_source_arrival_id_idx');
                $table->dropColumn('source_arrival_id');
            }

            if (Schema::hasColumn('location_inventory_adjustments', 'source_receive_id')) {
                $table->dropIndex('lia_source_receive_id_idx');
                $table->dropColumn('source_receive_id');
            }
        });
    }
};
