<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcon_orders')) {
            return;
        }

        Schema::table('subcon_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('subcon_orders', 'contract_no')) {
                $table->string('contract_no', 100)->nullable()->after('order_no');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subcon_orders')) {
            return;
        }

        Schema::table('subcon_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subcon_orders', 'contract_no')) {
                $table->dropColumn('contract_no');
            }
        });
    }
};
