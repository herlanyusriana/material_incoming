<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->string('ng_reason')->nullable()->after('ng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->dropColumn('ng_reason');
        });
    }
};
