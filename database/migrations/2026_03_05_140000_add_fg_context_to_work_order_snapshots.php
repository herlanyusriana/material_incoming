<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('work_order_bom_snapshots')) {
            Schema::table('work_order_bom_snapshots', function (Blueprint $table) {
                if (!Schema::hasColumn('work_order_bom_snapshots', 'fg_part_id')) {
                    $table->unsignedBigInteger('fg_part_id')->nullable()->after('line_no');
                    $table->index(['work_order_id', 'fg_part_id'], 'wo_bom_snap_fg_idx');
                }
                if (!Schema::hasColumn('work_order_bom_snapshots', 'fg_part_no')) {
                    $table->string('fg_part_no')->nullable()->after('fg_part_id');
                }
                if (!Schema::hasColumn('work_order_bom_snapshots', 'fg_part_name')) {
                    $table->string('fg_part_name')->nullable()->after('fg_part_no');
                }
            });
        }

        if (Schema::hasTable('work_order_requirement_snapshots')) {
            Schema::table('work_order_requirement_snapshots', function (Blueprint $table) {
                if (!Schema::hasColumn('work_order_requirement_snapshots', 'fg_part_id')) {
                    $table->unsignedBigInteger('fg_part_id')->nullable()->after('work_order_id');
                    $table->index(['work_order_id', 'fg_part_id'], 'wo_req_snap_fg_idx');
                }
                if (!Schema::hasColumn('work_order_requirement_snapshots', 'fg_part_no')) {
                    $table->string('fg_part_no')->nullable()->after('fg_part_id');
                }
                if (!Schema::hasColumn('work_order_requirement_snapshots', 'fg_part_name')) {
                    $table->string('fg_part_name')->nullable()->after('fg_part_no');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('work_order_requirement_snapshots')) {
            Schema::table('work_order_requirement_snapshots', function (Blueprint $table) {
                foreach (['fg_part_name', 'fg_part_no', 'fg_part_id'] as $col) {
                    if (Schema::hasColumn('work_order_requirement_snapshots', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('work_order_bom_snapshots')) {
            Schema::table('work_order_bom_snapshots', function (Blueprint $table) {
                foreach (['fg_part_name', 'fg_part_no', 'fg_part_id'] as $col) {
                    if (Schema::hasColumn('work_order_bom_snapshots', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

