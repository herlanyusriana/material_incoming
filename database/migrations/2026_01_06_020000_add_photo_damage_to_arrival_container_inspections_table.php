<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('arrival_container_inspections')) {
            return;
        }

        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_container_inspections', 'photo_damage')) {
                return;
            }

            $table->string('photo_damage')->nullable()->after('photo_seal');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('arrival_container_inspections')) {
            return;
        }

        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_container_inspections', 'photo_damage')) {
                return;
            }

            $table->dropColumn('photo_damage');
        });
    }
};

