<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_daily_plan_rows', function (Blueprint $table) {
            if (!Schema::hasColumn('outgoing_daily_plan_rows', 'gci_part_id')) {
                $table->foreignId('gci_part_id')->nullable()->after('part_no')->constrained('gci_parts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_daily_plan_rows', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_daily_plan_rows', 'gci_part_id')) {
                try {
                    $table->dropForeign(['gci_part_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('gci_part_id');
            }
        });
    }
};
