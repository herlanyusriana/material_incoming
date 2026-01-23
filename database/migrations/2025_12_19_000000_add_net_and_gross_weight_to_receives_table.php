<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            if (!Schema::hasColumn('receives', 'net_weight')) {
                $table->decimal('net_weight', 8, 2)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('receives', 'gross_weight')) {
                $table->decimal('gross_weight', 8, 2)->nullable()->after('net_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('receives', 'net_weight')) {
                $drops[] = 'net_weight';
            }
            if (Schema::hasColumn('receives', 'gross_weight')) {
                $drops[] = 'gross_weight';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
