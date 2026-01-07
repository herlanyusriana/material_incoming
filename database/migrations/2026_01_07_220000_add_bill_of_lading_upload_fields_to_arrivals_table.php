<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'bill_of_lading_status')) {
                $table->string('bill_of_lading_status', 20)->nullable()->after('bill_of_lading');
            }
            if (!Schema::hasColumn('arrivals', 'bill_of_lading_file')) {
                $table->string('bill_of_lading_file')->nullable()->after('bill_of_lading_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'bill_of_lading_file')) {
                $table->dropColumn('bill_of_lading_file');
            }
            if (Schema::hasColumn('arrivals', 'bill_of_lading_status')) {
                $table->dropColumn('bill_of_lading_status');
            }
        });
    }
};

