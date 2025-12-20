<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('arrival_items', 'unit_goods')) {
            return;
        }

        Schema::table('arrival_items', function (Blueprint $table) {
            $table->string('unit_goods', 20)->nullable()->after('qty_goods');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('arrival_items', 'unit_goods')) {
            return;
        }

        Schema::table('arrival_items', function (Blueprint $table) {
            $table->dropColumn('unit_goods');
        });
    }
};

