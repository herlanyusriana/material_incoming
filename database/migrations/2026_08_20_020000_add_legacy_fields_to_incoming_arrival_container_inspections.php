<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('incoming_arrival_container_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('status');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'seal_code')) {
                $table->string('seal_code')->nullable()->after('driver_name');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'issues_left')) {
                $table->json('issues_left')->nullable()->after('seal_code');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'issues_right')) {
                $table->json('issues_right')->nullable()->after('issues_left');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'issues_front')) {
                $table->json('issues_front')->nullable()->after('issues_right');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'issues_back')) {
                $table->json('issues_back')->nullable()->after('issues_front');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'issues_inside')) {
                $table->json('issues_inside')->nullable()->after('issues_back');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'issues_seal')) {
                $table->json('issues_seal')->nullable()->after('issues_inside');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'photo_damage')) {
                $table->string('photo_damage')->nullable()->after('photo_seal');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'photo_damage_1')) {
                $table->string('photo_damage_1')->nullable()->after('photo_damage');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'photo_damage_2')) {
                $table->string('photo_damage_2')->nullable()->after('photo_damage_1');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'photo_damage_3')) {
                $table->string('photo_damage_3')->nullable()->after('photo_damage_2');
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'inspected_by')) {
                $table->foreignId('inspected_by')->nullable()->after('photo_damage_3')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('incoming_arrival_container_inspections', 'inspected_at')) {
                $table->timestamp('inspected_at')->nullable()->after('inspected_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incoming_arrival_container_inspections', function (Blueprint $table) {
            $table->dropColumn([
                'driver_name',
                'seal_code',
                'issues_left',
                'issues_right',
                'issues_front',
                'issues_back',
                'issues_inside',
                'issues_seal',
                'photo_damage',
                'photo_damage_1',
                'photo_damage_2',
                'photo_damage_3',
                'inspected_by',
                'inspected_at',
            ]);
        });
    }
};
