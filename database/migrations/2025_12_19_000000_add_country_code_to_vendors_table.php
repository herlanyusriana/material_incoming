<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('vendor_name');
            }
            try {
                $table->index('country_code');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            try {
                $table->dropIndex(['country_code']);
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('vendors', 'country_code')) {
                $table->dropColumn('country_code');
            }
        });
    }
};
