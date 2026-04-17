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
        Schema::table('subcon_order_receives', function (Blueprint $table) {
            $table->string('sj_file_path')->nullable()->after('reject_posted_to_wh_at');
            $table->string('invoice_file_path')->nullable()->after('sj_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcon_order_receives', function (Blueprint $table) {
            $table->dropColumn(['sj_file_path', 'invoice_file_path']);
        });
    }
};
