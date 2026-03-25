<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('arrivals')) {
            return;
        }

        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'pen_no')) {
                $table->string('pen_no', 100)->nullable()->after('bill_of_lading');
            }

            if (!Schema::hasColumn('arrivals', 'aju_no')) {
                $table->string('aju_no', 100)->nullable()->after('pen_no');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('arrivals')) {
            return;
        }

        Schema::table('arrivals', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('arrivals', 'aju_no')) {
                $drops[] = 'aju_no';
            }

            if (Schema::hasColumn('arrivals', 'pen_no')) {
                $drops[] = 'pen_no';
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
