<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('incoming_arrival_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('incoming_arrival_inspections', 'issues_left')) {
                $table->json('issues_left')->nullable()->after('status');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'issues_right')) {
                $table->json('issues_right')->nullable()->after('issues_left');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'issues_front')) {
                $table->json('issues_front')->nullable()->after('issues_right');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'issues_back')) {
                $table->json('issues_back')->nullable()->after('issues_front');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'issues_inside')) {
                $table->json('issues_inside')->nullable()->after('issues_back');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'issues_seal')) {
                $table->json('issues_seal')->nullable()->after('issues_inside');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'photo_inside')) {
                $table->string('photo_inside')->nullable()->after('photo_back');
            }
            if (!Schema::hasColumn('incoming_arrival_inspections', 'inspected_at')) {
                $table->timestamp('inspected_at')->nullable()->after('inspected_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incoming_arrival_inspections', function (Blueprint $table) {
            $table->dropColumn([
                'issues_left',
                'issues_right',
                'issues_front',
                'issues_back',
                'issues_inside',
                'issues_seal',
                'photo_inside',
                'inspected_at',
            ]);
        });
    }
};
