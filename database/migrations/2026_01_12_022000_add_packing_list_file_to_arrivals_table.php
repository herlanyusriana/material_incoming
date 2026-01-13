<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'packing_list_file')) {
                $table->string('packing_list_file')->nullable()->after('invoice_file');
            }
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'packing_list_file')) {
                $table->dropColumn('packing_list_file');
            }
        });
    }
};

