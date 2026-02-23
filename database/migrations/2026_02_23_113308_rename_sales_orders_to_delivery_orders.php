<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename columns in sales_orders table
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->renameColumn('so_no', 'do_no');
            $table->renameColumn('so_date', 'do_date');
        });

        // 2. Rename sales_order_items table columns
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->renameColumn('sales_order_id', 'delivery_order_id');
        });

        // 3. Rename column in outgoing_picking_fgs
        Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
            $table->renameColumn('sales_order_id', 'delivery_order_id');
        });

        // 4. Rename column in delivery_items
        if (Schema::hasColumn('delivery_items', 'sales_order_id')) {
            Schema::table('delivery_items', function (Blueprint $table) {
                $table->renameColumn('sales_order_id', 'delivery_order_id');
            });
        }

        // 5. Rename column in delivery_notes
        if (Schema::hasColumn('delivery_notes', 'sales_order_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->renameColumn('sales_order_id', 'delivery_order_id');
            });
        }

        // 6. Rename pivot table columns
        if (Schema::hasTable('delivery_note_sales_order')) {
            Schema::table('delivery_note_sales_order', function (Blueprint $table) {
                $table->renameColumn('sales_order_id', 'delivery_order_id');
            });
            Schema::rename('delivery_note_sales_order', 'delivery_note_delivery_order');
        }

        // 7. Rename main tables
        Schema::rename('sales_order_items', 'delivery_order_items');
        Schema::rename('sales_orders', 'delivery_orders');
    }

    public function down(): void
    {
        // Reverse table renames
        Schema::rename('delivery_orders', 'sales_orders');
        Schema::rename('delivery_order_items', 'sales_order_items');

        if (Schema::hasTable('delivery_note_delivery_order')) {
            Schema::rename('delivery_note_delivery_order', 'delivery_note_sales_order');
            Schema::table('delivery_note_sales_order', function (Blueprint $table) {
                $table->renameColumn('delivery_order_id', 'sales_order_id');
            });
        }

        if (Schema::hasColumn('delivery_notes', 'delivery_order_id')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->renameColumn('delivery_order_id', 'sales_order_id');
            });
        }

        if (Schema::hasColumn('delivery_items', 'delivery_order_id')) {
            Schema::table('delivery_items', function (Blueprint $table) {
                $table->renameColumn('delivery_order_id', 'sales_order_id');
            });
        }

        Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
            $table->renameColumn('delivery_order_id', 'sales_order_id');
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->renameColumn('delivery_order_id', 'sales_order_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->renameColumn('do_no', 'so_no');
            $table->renameColumn('do_date', 'so_date');
        });
    }
};
