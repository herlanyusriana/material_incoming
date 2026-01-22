<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('arrival_container_inspections')) {
            Schema::table('arrival_container_inspections', function (Blueprint $table) {
                if (!Schema::hasColumn('arrival_container_inspections', 'issues_inside')) {
                    $table->json('issues_inside')->nullable()->after('photo_inside');
                }
                if (!Schema::hasColumn('arrival_container_inspections', 'issues_seal')) {
                    $table->json('issues_seal')->nullable()->after('issues_inside');
                }
            });
        }

        // Legacy per-invoice inspection (keep parity).
        if (Schema::hasTable('arrival_inspections')) {
            Schema::table('arrival_inspections', function (Blueprint $table) {
                if (!Schema::hasColumn('arrival_inspections', 'issues_inside')) {
                    $table->json('issues_inside')->nullable()->after('issues_back');
                }
                if (!Schema::hasColumn('arrival_inspections', 'issues_seal')) {
                    $table->json('issues_seal')->nullable()->after('issues_inside');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('arrival_container_inspections')) {
            Schema::table('arrival_container_inspections', function (Blueprint $table) {
                if (Schema::hasColumn('arrival_container_inspections', 'issues_seal')) {
                    $table->dropColumn('issues_seal');
                }
                if (Schema::hasColumn('arrival_container_inspections', 'issues_inside')) {
                    $table->dropColumn('issues_inside');
                }
            });
        }

        if (Schema::hasTable('arrival_inspections')) {
            Schema::table('arrival_inspections', function (Blueprint $table) {
                if (Schema::hasColumn('arrival_inspections', 'issues_seal')) {
                    $table->dropColumn('issues_seal');
                }
                if (Schema::hasColumn('arrival_inspections', 'issues_inside')) {
                    $table->dropColumn('issues_inside');
                }
            });
        }
    }
};

