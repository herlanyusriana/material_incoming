<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table
                ->unsignedInteger('bundle_qty')
                ->default(1)
                ->after('bundle_unit');
        });
    }

    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table->dropColumn('bundle_qty');
        });
    }
};

