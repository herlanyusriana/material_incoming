<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outgoing_po_items', function (Blueprint $table) {
            $table->integer('fulfilled_qty')->default(0)->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_po_items', function (Blueprint $table) {
            $table->dropColumn('fulfilled_qty');
        });
    }
};
