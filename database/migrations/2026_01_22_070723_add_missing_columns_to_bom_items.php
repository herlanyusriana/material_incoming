<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'line_no')) {
                $table->integer('line_no')->nullable()->after('id');
            }
            if (!Schema::hasColumn('bom_items', 'process_name')) {
                $table->string('process_name')->nullable()->after('line_no');
            }
            if (!Schema::hasColumn('bom_items', 'machine_name')) {
                $table->string('machine_name')->nullable()->after('process_name');
            }
            if (!Schema::hasColumn('bom_items', 'wip_part_id')) {
                $table->foreignId('wip_part_id')->nullable()->constrained('gci_parts')->nullOnDelete()->after('machine_name');
            }
            // wip_part_no already likely exists from previous migration, but check just in case or skip
            
            if (!Schema::hasColumn('bom_items', 'wip_qty')) {
                $table->decimal('wip_qty', 18, 4)->nullable()->after('wip_part_no');
            }
            if (!Schema::hasColumn('bom_items', 'wip_uom')) {
                $table->string('wip_uom', 20)->nullable()->after('wip_qty');
            }
            if (!Schema::hasColumn('bom_items', 'wip_part_name')) {
                $table->string('wip_part_name')->nullable()->after('wip_uom');
            }
            
            if (!Schema::hasColumn('bom_items', 'material_size')) {
                $table->string('material_size')->nullable()->after('wip_part_name');
            }
            if (!Schema::hasColumn('bom_items', 'material_spec')) {
                $table->string('material_spec')->nullable()->after('material_size');
            }
            if (!Schema::hasColumn('bom_items', 'material_name')) {
                $table->string('material_name')->nullable()->after('material_spec');
            }
            if (!Schema::hasColumn('bom_items', 'special')) {
                $table->string('special')->nullable()->after('material_name');
            }
            if (!Schema::hasColumn('bom_items', 'consumption_uom_id')) {
                $table->foreignId('consumption_uom_id')->nullable()->constrained('uoms')->nullOnDelete()->after('consumption_uom');
            }
            if (!Schema::hasColumn('bom_items', 'wip_uom_id')) {
                $table->foreignId('wip_uom_id')->nullable()->constrained('uoms')->nullOnDelete()->after('wip_uom');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropForeign(['wip_part_id']);
            $table->dropForeign(['consumption_uom_id']);
            $table->dropForeign(['wip_uom_id']);
            
            $table->dropColumn([
                'line_no',
                'process_name',
                'machine_name',
                'wip_part_id',
                'wip_qty',
                'wip_uom',
                'wip_part_name',
                'material_size',
                'material_spec',
                'material_name',
                'special',
                'consumption_uom_id',
                'wip_uom_id',
            ]);
        });
    }
};
