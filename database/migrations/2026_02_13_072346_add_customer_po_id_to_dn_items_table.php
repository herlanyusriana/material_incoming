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
        if (!Schema::hasColumn('dn_items', 'customer_po_id')) {
            Schema::table('dn_items', function (Blueprint $table) {
                $table->foreignId('customer_po_id')->nullable()->constrained('customer_pos')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dn_items', function (Blueprint $table) {
            $table->dropForeign(['customer_po_id']);
            $table->dropColumn('customer_po_id');
        });
    }
};
