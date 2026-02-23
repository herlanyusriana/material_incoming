<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('related_so')->nullable()->after('transaction_no');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('related_wo')->nullable()->after('transaction_no');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('related_so');
        });

        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn('related_wo');
        });
    }
};
