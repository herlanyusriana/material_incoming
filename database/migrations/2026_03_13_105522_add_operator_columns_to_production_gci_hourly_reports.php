<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->string('operator_name')->nullable()->after('ng');
            $table->string('shift')->nullable()->after('operator_name');
        });
    }

    public function down(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->dropColumn(['operator_name', 'shift']);
        });
    }
};
