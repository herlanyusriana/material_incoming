<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the four MRP planning phases to the forecast → stock → MRP → PO flow:
 *
 *   1. Safety Stock       -> gci_parts.safety_stock
 *   2. MOQ (per vendor)   -> gci_part_vendor.min_order_qty + gci_parts.order_multiple
 *   3. ETA weekly         -> mrp_purchase_plans.eta_week (YYYY-Www)
 *   4. Approval           -> mrp_purchase_plans/production_plans.status + approved_by/at
 *
 * Plus PO actualization  -> purchase_order_items.qty_received
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ── Safety stock + order multiple on the internal part master ──
        if (Schema::hasTable('gci_parts') && !Schema::hasColumn('gci_parts', 'safety_stock')) {
            Schema::table('gci_parts', function (Blueprint $table) {
                $table->decimal('safety_stock', 20, 4)->default(0)->after('subcount_uom');
            });
        }

        if (Schema::hasTable('gci_parts') && !Schema::hasColumn('gci_parts', 'order_multiple')) {
            Schema::table('gci_parts', function (Blueprint $table) {
                $table->decimal('order_multiple', 20, 4)->default(0)->after('safety_stock');
            });
        }

        // ── MOQ + lead time per vendor-part link ──
        if (Schema::hasTable('gci_part_vendor') && !Schema::hasColumn('gci_part_vendor', 'min_order_qty')) {
            Schema::table('gci_part_vendor', function (Blueprint $table) {
                $table->decimal('min_order_qty', 20, 4)->default(0)->after('price');
            });
        }

        if (Schema::hasTable('gci_part_vendor') && !Schema::hasColumn('gci_part_vendor', 'lead_time_days')) {
            Schema::table('gci_part_vendor', function (Blueprint $table) {
                $table->unsignedInteger('lead_time_days')->nullable()->after('min_order_qty');
            });
        }

        // ── Approval + ETA week on purchase (buy) plans ──
        if (Schema::hasTable('mrp_purchase_plans')) {
            if (!Schema::hasColumn('mrp_purchase_plans', 'eta_week')) {
                Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                    $table->string('eta_week', 8)->nullable()->after('plan_date');
                    $table->index('eta_week');
                });
            }

            if (!Schema::hasColumn('mrp_purchase_plans', 'status')) {
                Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                    $table->string('status', 20)->default('pending')->after('eta_week'); // pending | approved | rejected
                    $table->index('status');
                });
            }

            if (!Schema::hasColumn('mrp_purchase_plans', 'approved_by')) {
                Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
                    $table->index('approved_by');
                });
            }

            if (!Schema::hasColumn('mrp_purchase_plans', 'approved_at')) {
                Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                });
            }
        }

        // ── Approval on production (make) plans ──
        if (Schema::hasTable('mrp_production_plans')) {
            if (!Schema::hasColumn('mrp_production_plans', 'status')) {
                Schema::table('mrp_production_plans', function (Blueprint $table) {
                    $table->string('status', 20)->default('pending')->after('planned_qty');
                    $table->index('status');
                });
            }

            if (!Schema::hasColumn('mrp_production_plans', 'approved_by')) {
                Schema::table('mrp_production_plans', function (Blueprint $table) {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
                    $table->index('approved_by');
                });
            }

            if (!Schema::hasColumn('mrp_production_plans', 'approved_at')) {
                Schema::table('mrp_production_plans', function (Blueprint $table) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                });
            }
        }

        // ── PO actualization: received quantity on the item line ──
        if (Schema::hasTable('purchase_order_items') && !Schema::hasColumn('purchase_order_items', 'qty_received')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->decimal('qty_received', 20, 4)->default(0)->after('qty');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchase_order_items', 'qty_received')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropColumn('qty_received');
            });
        }

        foreach (['mrp_production_plans', 'mrp_purchase_plans'] as $table) {
            if (Schema::hasColumn($table, 'approved_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('approved_at');
                });
            }
            if (Schema::hasColumn($table, 'approved_by')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('approved_by');
                });
            }
            if (Schema::hasColumn($table, 'status')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('status');
                });
            }
        }

        if (Schema::hasColumn('mrp_purchase_plans', 'eta_week')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $t) {
                $t->dropColumn('eta_week');
            });
        }

        if (Schema::hasColumn('gci_part_vendor', 'lead_time_days')) {
            Schema::table('gci_part_vendor', function (Blueprint $t) {
                $t->dropColumn('lead_time_days');
            });
        }

        if (Schema::hasColumn('gci_part_vendor', 'min_order_qty')) {
            Schema::table('gci_part_vendor', function (Blueprint $t) {
                $t->dropColumn('min_order_qty');
            });
        }

        if (Schema::hasColumn('gci_parts', 'order_multiple')) {
            Schema::table('gci_parts', function (Blueprint $t) {
                $t->dropColumn('order_multiple');
            });
        }

        if (Schema::hasColumn('gci_parts', 'safety_stock')) {
            Schema::table('gci_parts', function (Blueprint $t) {
                $t->dropColumn('safety_stock');
            });
        }
    }
};
