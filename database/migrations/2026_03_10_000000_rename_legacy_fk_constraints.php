<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename legacy FK constraint names left behind by the
 * sales_orders -> delivery_orders table rename.
 *
 * MySQL does NOT rename a table's foreign-key constraint names when you
 * RENAME TABLE (and FK names are unique per SCHEMA, not per table). The
 * renamed tables `delivery_orders` / `delivery_order_items` therefore still
 * carry `sales_orders_*_foreign` / `sales_order_items_*_foreign` names.
 *
 * Later, the new default schema (create_outgoing_tables) re-CREATES a fresh
 * `sales_orders` / `sales_order_items` pair whose auto-named FKs collide
 * schema-wide with those leftovers -> `Duplicate foreign key constraint name`
 * on `migrate:fresh`.
 *
 * MySQL has no `RENAME CONSTRAINT` (that is MariaDB), so we reproduce each
 * FK by dropping the legacy name and re-adding it under the `delivery_*`
 * name with the same column / referenced table / ON DELETE rule.
 */
return new class extends Migration
{
    // [table, old, new, column, ref_table, ref_col, delete_rule]
    private array $renames = [
        ['delivery_orders', 'sales_orders_created_by_foreign', 'delivery_orders_created_by_foreign', 'created_by', 'users', 'id', 'SET NULL'],
        ['delivery_orders', 'sales_orders_customer_id_foreign', 'delivery_orders_customer_id_foreign', 'customer_id', 'customers', 'id', 'CASCADE'],
        ['delivery_orders', 'sales_orders_delivery_plan_id_foreign', 'delivery_orders_delivery_plan_id_foreign', 'delivery_plan_id', 'delivery_plans', 'id', 'SET NULL'],
        ['delivery_orders', 'sales_orders_delivery_stop_id_foreign', 'delivery_orders_delivery_stop_id_foreign', 'delivery_stop_id', 'delivery_stops', 'id', 'SET NULL'],
        ['delivery_order_items', 'sales_order_items_gci_part_id_foreign', 'delivery_order_items_gci_part_id_foreign', 'gci_part_id', 'gci_parts', 'id', 'CASCADE'],
        ['delivery_order_items', 'sales_order_items_sales_order_id_foreign', 'delivery_order_items_delivery_order_id_foreign', 'delivery_order_id', 'delivery_orders', 'id', 'CASCADE'],
    ];

    private function hasConstraint(string $table, string $name): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $name]
        );
    }

    public function up(): void
    {
        foreach ($this->renames as [$table, $old, $new, $col, $refTable, $refCol, $rule]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if ($this->hasConstraint($table, $old) && !$this->hasConstraint($table, $new)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$old}`");
                DB::statement(
                    "ALTER TABLE `{$table}` ADD CONSTRAINT `{$new}` FOREIGN KEY (`{$col}`) REFERENCES `{$refTable}` (`{$refCol}`) ON DELETE {$rule} ON UPDATE NO ACTION"
                );
            }
        }
    }

    public function down(): void
    {
        foreach ($this->renames as [$table, $old, $new, $col, $refTable, $refCol, $rule]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if ($this->hasConstraint($table, $new) && !$this->hasConstraint($table, $old)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$new}`");
                DB::statement(
                    "ALTER TABLE `{$table}` ADD CONSTRAINT `{$old}` FOREIGN KEY (`{$col}`) REFERENCES `{$refTable}` (`{$refCol}`) ON DELETE {$rule} ON UPDATE NO ACTION"
                );
            }
        }
    }
};
