<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('incoming_arrival_items', function (Blueprint $table) {
            if (!Schema::hasColumn('incoming_arrival_items', 'vendor_part_id')) {
                $table->foreignId('vendor_part_id')->nullable()->after('gci_part_id')->constrained('vendor_parts')->nullOnDelete();
            }
        });

        Schema::table('incoming_receives', function (Blueprint $table) {
            if (!Schema::hasColumn('incoming_receives', 'invoice_no')) {
                $table->string('invoice_no')->nullable()->after('gross_weight');
            }
            if (!Schema::hasColumn('incoming_receives', 'delivery_note_no')) {
                $table->string('delivery_note_no')->nullable()->after('invoice_no');
            }
            if (!Schema::hasColumn('incoming_receives', 'truck_no')) {
                $table->string('truck_no')->nullable()->after('delivery_note_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('incoming_arrival_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_part_id']);
            $table->dropColumn('vendor_part_id');
        });

        Schema::table('incoming_receives', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'delivery_note_no', 'truck_no']);
        });
    }
};
