<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            $table->string('photo_seal')->nullable()->after('photo_inside');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            $table->dropColumn('photo_seal');
        });
    }
};

