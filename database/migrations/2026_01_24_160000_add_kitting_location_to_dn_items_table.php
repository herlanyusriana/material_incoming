<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('dn_items')) {
            return;
        }

        Schema::table('dn_items', function (Blueprint $table) {
            if (!Schema::hasColumn('dn_items', 'kitting_location_code')) {
                $table->string('kitting_location_code', 50)->nullable()->after('qty');
                $table->index('kitting_location_code');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('dn_items')) {
            return;
        }

        Schema::table('dn_items', function (Blueprint $table) {
            if (Schema::hasColumn('dn_items', 'kitting_location_code')) {
                $table->dropIndex(['kitting_location_code']);
                $table->dropColumn('kitting_location_code');
            }
        });
    }
};

