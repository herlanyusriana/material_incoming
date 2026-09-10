<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Kolom yang dibutuhkan master-data:import tapi belum ada di beberapa
     * environment (drift schema lokal vs migration core).
     */
    public function up(): void
    {
        if (Schema::hasTable('vendors') && !Schema::hasColumn('vendors', 'vendor_code')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('vendor_code')->nullable()->unique()->after('vendor_name');
            });
        }

        if (Schema::hasTable('boms') && !Schema::hasColumn('boms', 'bom_no')) {
            Schema::table('boms', function (Blueprint $table) {
                $table->string('bom_no')->nullable()->after('part_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('boms') && Schema::hasColumn('boms', 'bom_no')) {
            Schema::table('boms', function (Blueprint $table) {
                $table->dropColumn('bom_no');
            });
        }

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'vendor_code')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropUnique(['vendor_code']);
                $table->dropColumn('vendor_code');
            });
        }
    }
};
