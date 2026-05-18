<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            if (!Schema::hasColumn('arrival_items', 'is_foc')) {
                $table->boolean('is_foc')->default(false)->after('total_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_items', 'is_foc')) {
                $table->dropColumn('is_foc');
            }
        });
    }
};
