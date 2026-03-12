<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->foreignId('production_order_id')->nullable()->after('production_gci_work_order_id')
                ->constrained('production_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropColumn('production_order_id');
        });
    }
};
