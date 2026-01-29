<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_parts', function (Blueprint $table) {
            $table->string('line')->nullable()->after('customer_part_name');
            $table->string('case_name')->nullable()->after('line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_parts', function (Blueprint $table) {
            $table->dropColumn(['line', 'case_name']);
        });
    }
};
