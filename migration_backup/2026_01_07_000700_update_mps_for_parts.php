<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mps', function (Blueprint $table) {
            if (!Schema::hasColumn('mps', 'part_id')) {
                $table->foreignId('part_id')->nullable()->after('id')->constrained('gci_parts')->nullOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('mps', 'minggu')) {
                $table->string('minggu', 8)->nullable()->after('period');
            }
        });

        Schema::table('mps', function (Blueprint $table) {
            if (Schema::hasColumn('mps', 'part_id') && Schema::hasColumn('mps', 'minggu')) {
                $table->unique(['part_id', 'minggu'], 'mps_part_minggu_unique');
                $table->index('minggu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mps', function (Blueprint $table) {
            if (Schema::hasColumn('mps', 'part_id') && Schema::hasColumn('mps', 'minggu')) {
                $table->dropUnique('mps_part_minggu_unique');
                $table->dropIndex(['minggu']);
            }
            if (Schema::hasColumn('mps', 'part_id')) {
                $table->dropConstrainedForeignId('part_id');
            }
            if (Schema::hasColumn('mps', 'minggu')) {
                $table->dropColumn('minggu');
            }
        });
    }
};
