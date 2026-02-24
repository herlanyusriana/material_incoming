<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'vendor_part_id')) {
                $table->unsignedBigInteger('vendor_part_id')->nullable()->after('part_id');
                $table->foreign('vendor_part_id')->references('id')->on('parts')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'vendor_part_id')) {
                $table->dropForeign(['vendor_part_id']);
                $table->dropColumn('vendor_part_id');
            }
        });
    }
};
