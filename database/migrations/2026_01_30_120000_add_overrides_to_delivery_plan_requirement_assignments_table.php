<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_plan_requirement_assignments')) {
            return;
        }

        Schema::table('delivery_plan_requirement_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_plan_requirement_assignments', 'line_type_override')) {
                $table->string('line_type_override')->nullable()->after('delivery_plan_id');
            }
            if (!Schema::hasColumn('delivery_plan_requirement_assignments', 'jig_capacity_nr1_override')) {
                $table->unsignedInteger('jig_capacity_nr1_override')->nullable()->after('line_type_override');
            }
            if (!Schema::hasColumn('delivery_plan_requirement_assignments', 'jig_capacity_nr2_override')) {
                $table->unsignedInteger('jig_capacity_nr2_override')->nullable()->after('jig_capacity_nr1_override');
            }
            if (!Schema::hasColumn('delivery_plan_requirement_assignments', 'uph_nr1_override')) {
                $table->decimal('uph_nr1_override', 12, 2)->nullable()->after('jig_capacity_nr2_override');
            }
            if (!Schema::hasColumn('delivery_plan_requirement_assignments', 'uph_nr2_override')) {
                $table->decimal('uph_nr2_override', 12, 2)->nullable()->after('uph_nr1_override');
            }
            if (!Schema::hasColumn('delivery_plan_requirement_assignments', 'notes')) {
                $table->string('notes', 255)->nullable()->after('uph_nr2_override');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('delivery_plan_requirement_assignments')) {
            return;
        }

        Schema::table('delivery_plan_requirement_assignments', function (Blueprint $table) {
            foreach ([
                'line_type_override',
                'jig_capacity_nr1_override',
                'jig_capacity_nr2_override',
                'uph_nr1_override',
                'uph_nr2_override',
                'notes',
            ] as $col) {
                if (Schema::hasColumn('delivery_plan_requirement_assignments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

