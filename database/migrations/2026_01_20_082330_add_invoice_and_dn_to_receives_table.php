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
            if (!Schema::hasColumn('receives', 'invoice_no')) {
                $table->string('invoice_no', 100)->nullable()->after('jo_po_number');
            }
            if (!Schema::hasColumn('receives', 'delivery_note_no')) {
                $table->string('delivery_note_no', 100)->nullable()->after('invoice_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('receives', 'invoice_no')) {
                $drops[] = 'invoice_no';
            }
            if (Schema::hasColumn('receives', 'delivery_note_no')) {
                $drops[] = 'delivery_note_no';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
