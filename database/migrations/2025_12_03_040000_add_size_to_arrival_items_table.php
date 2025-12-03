<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            $table->string('size', 100)->nullable()->after('part_id')->comment('Format: 1.00 x 200.0 x C');
        });
    }

    public function down(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
