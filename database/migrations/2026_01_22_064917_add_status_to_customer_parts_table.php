<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_parts', 'status')) {
                $table->string('status')->default('active')->after('customer_part_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_parts', function (Blueprint $table) {
            if (Schema::hasColumn('customer_parts', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
