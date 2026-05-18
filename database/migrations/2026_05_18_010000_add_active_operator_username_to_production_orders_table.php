<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('production_orders', 'active_operator_username')) {
                $table->string('active_operator_username')->nullable()->after('active_operator_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'active_operator_username')) {
                $table->dropColumn('active_operator_username');
            }
        });
    }
};
