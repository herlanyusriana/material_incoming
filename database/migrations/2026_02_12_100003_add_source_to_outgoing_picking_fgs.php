<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
            $table->string('source', 20)->default('daily_plan')->after('gci_part_id');
            $table->foreignId('outgoing_po_item_id')->nullable()->after('source')
                ->constrained('outgoing_po_items')->nullOnDelete();

            // Drop old unique, add new one with source
            $table->dropUnique(['delivery_date', 'gci_part_id']);
            $table->unique(['delivery_date', 'gci_part_id', 'source'], 'opf_date_part_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
            $table->dropUnique('opf_date_part_source_unique');
            $table->dropForeign(['outgoing_po_item_id']);
            $table->dropColumn(['source', 'outgoing_po_item_id']);
            $table->unique(['delivery_date', 'gci_part_id']);
        });
    }
};
