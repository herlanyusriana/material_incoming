<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->decimal('scrap_factor', 5, 4)->default(0)->after('usage_qty')
                ->comment('Scrap rate (0-1, e.g., 0.05 = 5% scrap)');
            $table->decimal('yield_factor', 5, 4)->default(1)->after('scrap_factor')
                ->comment('Yield rate (0-1, e.g., 0.95 = 95% yield)');
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn(['scrap_factor', 'yield_factor']);
        });
    }
};
