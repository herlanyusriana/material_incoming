<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bom_items')) {
            return;
        }

        $afterColumn = Schema::hasColumn('bom_items', 'make_or_buy') ? 'make_or_buy' : 'usage_qty';

        Schema::table('bom_items', function (Blueprint $table) use ($afterColumn) {
            if (!Schema::hasColumn('bom_items', 'special')) {
                $table->string('special', 50)->nullable()->after($afterColumn);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bom_items') || !Schema::hasColumn('bom_items', 'special')) {
            return;
        }

        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn('special');
        });
    }
};
