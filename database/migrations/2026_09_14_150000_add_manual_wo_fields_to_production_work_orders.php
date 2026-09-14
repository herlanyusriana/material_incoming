<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WO Manual sederhana: material utama + sumber invoice/tag RM (genealogi FG).
     */
    public function up(): void
    {
        Schema::table('production_work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('main_rm_part_id')->nullable()->after('bom_id');
            $table->string('rm_invoice_no')->nullable()->after('main_rm_part_id');
            $table->string('rm_tag')->nullable()->after('rm_invoice_no');

            $table->foreign('main_rm_part_id')->references('id')->on('gci_parts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_work_orders', function (Blueprint $table) {
            $table->dropForeign(['main_rm_part_id']);
            $table->dropColumn(['main_rm_part_id', 'rm_invoice_no', 'rm_tag']);
        });
    }
};