<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('incoming_arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('incoming_arrivals', 'container_numbers')) {
                $table->text('container_numbers')->nullable()->after('country');
            }
            if (!Schema::hasColumn('incoming_arrivals', 'seal_code')) {
                $table->string('seal_code')->nullable()->after('container_numbers');
            }
            if (!Schema::hasColumn('incoming_arrivals', 'hs_codes')) {
                $table->text('hs_codes')->nullable()->after('hs_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incoming_arrivals', function (Blueprint $table) {
            $table->dropColumn(['container_numbers', 'seal_code', 'hs_codes']);
        });
    }
};
