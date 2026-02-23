<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->string('transaction_no')->nullable()->unique()->after('arrival_no');
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropColumn('transaction_no');
        });
    }
};
