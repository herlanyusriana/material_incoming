<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            $table->integer('qty_goods')->default(0)->after('qty_bundle');
        });

        DB::statement('UPDATE arrival_items SET qty_goods = qty_600ds');

        Schema::table('arrival_items', function (Blueprint $table) {
            $table->dropColumn('qty_600ds');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            $table->integer('qty_600ds')->default(0)->after('qty_bundle');
        });

        DB::statement('UPDATE arrival_items SET qty_600ds = qty_goods');

        Schema::table('arrival_items', function (Blueprint $table) {
            $table->dropColumn('qty_goods');
        });
    }
};
