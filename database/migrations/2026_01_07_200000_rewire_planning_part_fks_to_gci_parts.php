<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function swapForeignKey(string $table, string $column, callable $addForeign): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($column) {
            try {
                $tableBlueprint->dropForeign([$column]);
            } catch (Throwable $e) {
                // Best effort: DB may not have the FK (or name differs).
            }
        });

        Schema::table($table, function (Blueprint $tableBlueprint) use ($addForeign) {
            $addForeign($tableBlueprint);
        });
    }

    public function up(): void
    {
        // Planning-related tables should reference gci_parts (Part GCI), not incoming parts.
        $this->swapForeignKey('customer_part_components', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        $this->swapForeignKey('customer_planning_rows', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $this->swapForeignKey('customer_pos', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $this->swapForeignKey('forecasts', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $this->swapForeignKey('mps', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $this->swapForeignKey('boms', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        $this->swapForeignKey('mrp_production_plans', 'part_id', function (Blueprint $table) {
            $table->foreign('part_id')
                ->references('id')
                ->on('gci_parts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Not attempting to revert back to incoming parts; this is a one-way correction.
    }
};
