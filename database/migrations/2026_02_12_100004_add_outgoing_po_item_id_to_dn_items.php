<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dn_items', function (Blueprint $table) {
            $table->foreignId('outgoing_po_item_id')->nullable()->after('customer_po_id')
                ->constrained('outgoing_po_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dn_items', function (Blueprint $table) {
            $table->dropForeign(['outgoing_po_item_id']);
            $table->dropColumn('outgoing_po_item_id');
        });
    }
};
