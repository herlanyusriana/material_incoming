<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'line_no')) {
                $table->unsignedInteger('line_no')->nullable()->after('id');
                $table->index(['bom_id', 'line_no']);
            }

            if (!Schema::hasColumn('bom_items', 'process_name')) {
                $table->string('process_name', 255)->nullable()->after('usage_qty');
            }
            if (!Schema::hasColumn('bom_items', 'machine_name')) {
                $table->string('machine_name', 255)->nullable()->after('process_name');
            }

            if (!Schema::hasColumn('bom_items', 'wip_part_id')) {
                $table->foreignId('wip_part_id')->nullable()->after('machine_name')->constrained('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
                $table->index('wip_part_id');
            }

            if (!Schema::hasColumn('bom_items', 'wip_qty')) {
                $table->decimal('wip_qty', 15, 3)->nullable()->after('wip_part_id');
            }
            if (!Schema::hasColumn('bom_items', 'wip_uom')) {
                $table->string('wip_uom', 20)->nullable()->after('wip_qty');
            }

            if (!Schema::hasColumn('bom_items', 'wip_part_name')) {
                $table->string('wip_part_name', 255)->nullable()->after('wip_uom');
            }

            if (!Schema::hasColumn('bom_items', 'material_size')) {
                $table->string('material_size', 255)->nullable()->after('wip_part_name');
            }
            if (!Schema::hasColumn('bom_items', 'material_spec')) {
                $table->string('material_spec', 255)->nullable()->after('material_size');
            }
            if (!Schema::hasColumn('bom_items', 'material_name')) {
                $table->string('material_name', 255)->nullable()->after('material_spec');
            }
            if (!Schema::hasColumn('bom_items', 'special')) {
                $table->string('special', 255)->nullable()->after('material_name');
            }

            if (!Schema::hasColumn('bom_items', 'consumption_uom')) {
                $table->string('consumption_uom', 20)->nullable()->after('special');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $drops = [
                'line_no',
                'process_name',
                'machine_name',
                'wip_qty',
                'wip_uom',
                'wip_part_name',
                'material_size',
                'material_spec',
                'material_name',
                'special',
                'consumption_uom',
            ];

            foreach ($drops as $col) {
                if (Schema::hasColumn('bom_items', $col)) {
                    $table->dropColumn($col);
                }
            }

            if (Schema::hasColumn('bom_items', 'wip_part_id')) {
                $table->dropConstrainedForeignId('wip_part_id');
            }
        });
    }
};

