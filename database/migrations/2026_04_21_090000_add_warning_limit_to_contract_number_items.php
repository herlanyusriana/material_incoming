<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contract_number_items')) {
            return;
        }

        Schema::table('contract_number_items', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_number_items', 'warning_limit_qty')) {
                $table->decimal('warning_limit_qty', 12, 4)->nullable()->after('target_qty');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contract_number_items') || !Schema::hasColumn('contract_number_items', 'warning_limit_qty')) {
            return;
        }

        Schema::table('contract_number_items', function (Blueprint $table) {
            $table->dropColumn('warning_limit_qty');
        });
    }
};
