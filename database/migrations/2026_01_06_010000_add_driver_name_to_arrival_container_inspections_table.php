<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_container_inspections', 'driver_name')) {
                return;
            }
            $table->string('driver_name', 150)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_container_inspections', 'driver_name')) {
                return;
            }
            $table->dropColumn('driver_name');
        });
    }
};

