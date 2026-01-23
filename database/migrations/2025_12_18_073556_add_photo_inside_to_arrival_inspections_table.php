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
        Schema::table('arrival_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_inspections', 'photo_inside')) {
                $table->string('photo_inside')->nullable()->after('photo_back');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_inspections', 'photo_inside')) {
                $table->dropColumn('photo_inside');
            }
        });
    }
};
