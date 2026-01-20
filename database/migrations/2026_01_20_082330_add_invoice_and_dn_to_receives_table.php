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
        Schema::table('receives', function (Blueprint $table) {
            $table->string('invoice_no', 100)->nullable()->after('jo_po_number');
            $table->string('delivery_note_no', 100)->nullable()->after('invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'delivery_note_no']);
        });
    }
};
