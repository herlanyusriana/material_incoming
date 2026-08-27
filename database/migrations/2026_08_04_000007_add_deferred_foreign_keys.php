<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds foreign key constraints that were deferred during initial table creation
 * to avoid circular dependencies and table-ordering issues.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── incoming_arrivals → trucking_companies / purchase_orders ──
        Schema::table('incoming_arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('incoming_arrivals', 'trucking_company_id')) {
                return;
            }
            $table->foreign('trucking_company_id', 'in_arrivals_trucking_fk')
                ->references('id')->on('trucking_companies')
                ->nullOnDelete();
        });

        // ── incoming_arrivals → purchase_orders (only if the table exists) ──
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('incoming_arrivals', 'purchase_order_id')) {
            Schema::table('incoming_arrivals', function (Blueprint $table) {
                $table->foreign('purchase_order_id', 'in_arrivals_po_fk')
                    ->references('id')->on('purchase_orders')
                    ->nullOnDelete();
            });
        }

        // ── production_orders → outgoing_daily_plan_cells ──
        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'daily_plan_cell_id')) {
                $table->foreign('daily_plan_cell_id', 'po_daily_cell_fk')
                    ->references('id')->on('outgoing_daily_plan_cells')
                    ->nullOnDelete();
            }
        });

        // ── production_hourly_reports → work_orders / downtimes ──
        Schema::table('production_hourly_reports', function (Blueprint $table) {
            if (Schema::hasColumn('production_hourly_reports', 'work_order_id')) {
                $table->foreign('work_order_id', 'phr_work_order_fk')
                    ->references('id')->on('production_work_orders')
                    ->nullOnDelete();
            }
            if (Schema::hasColumn('production_hourly_reports', 'offline_id')) {
                $table->foreign('offline_id', 'phr_offline_fk')
                    ->references('id')->on('production_downtimes')
                    ->nullOnDelete();
            }
        });

        // ── production_downtimes → work_orders ──
        Schema::table('production_downtimes', function (Blueprint $table) {
            if (Schema::hasColumn('production_downtimes', 'work_order_id')) {
                $table->foreign('work_order_id', 'pd_work_order_fk')
                    ->references('id')->on('production_work_orders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_downtimes', function (Blueprint $table) {
            $table->dropForeign('pd_work_order_fk');
        });
        Schema::table('production_hourly_reports', function (Blueprint $table) {
            $table->dropForeign('phr_offline_fk');
            $table->dropForeign('phr_work_order_fk');
        });
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign('po_daily_cell_fk');
        });
        Schema::table('incoming_arrivals', function (Blueprint $table) {
            $table->dropForeign('in_arrivals_po_fk');
            $table->dropForeign('in_arrivals_trucking_fk');
        });
    }
};