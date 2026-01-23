<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'hs_codes')) {
                $table->text('hs_codes')
                    ->nullable()
                    ->after('price_term')
                    ->comment('Multiple HS codes, separated by newline/comma.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'hs_codes')) {
                $table->dropColumn('hs_codes');
            }
        });
    }
};
