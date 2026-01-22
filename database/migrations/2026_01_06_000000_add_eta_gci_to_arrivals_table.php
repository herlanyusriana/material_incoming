<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'ETA_GCI')) {
                return;
            }
            $table->date('ETA_GCI')->nullable()->after('eta_date');
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'ETA_GCI')) {
                return;
            }
            $table->dropColumn('ETA_GCI');
        });
    }
};
