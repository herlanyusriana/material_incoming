<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('wo_material_allocations', 'requirement_id')) {
            Schema::table('wo_material_allocations', function (Blueprint $table) {
                $table->unsignedBigInteger('requirement_id')->nullable()->after('work_order_id')->index();
                $table->foreign('requirement_id')->references('id')->on('wo_requirements')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wo_material_allocations', 'requirement_id')) {
            Schema::table('wo_material_allocations', function (Blueprint $table) {
                $table->dropForeign(['requirement_id']);
                $table->dropColumn('requirement_id');
            });
        }
    }
};
