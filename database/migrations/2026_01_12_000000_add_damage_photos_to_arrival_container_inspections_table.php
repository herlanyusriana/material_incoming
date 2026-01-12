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
            if (!Schema::hasColumn('arrival_container_inspections', 'photo_damage_1')) {
                $table->string('photo_damage_1')->nullable()->after('photo_seal');
            }
            if (!Schema::hasColumn('arrival_container_inspections', 'photo_damage_2')) {
                $table->string('photo_damage_2')->nullable()->after('photo_damage_1');
            }
            if (!Schema::hasColumn('arrival_container_inspections', 'photo_damage_3')) {
                $table->string('photo_damage_3')->nullable()->after('photo_damage_2');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('arrival_container_inspections')) {
            return;
        }

        Schema::table('arrival_container_inspections', function (Blueprint $table) {
            foreach (['photo_damage_3', 'photo_damage_2', 'photo_damage_1'] as $col) {
                if (Schema::hasColumn('arrival_container_inspections', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

