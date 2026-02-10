<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->foreignId('incoming_part_id')
                ->nullable()
                ->comment('Reference to Parts Incoming (parts table) for RM items')
                ->constrained('parts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropForeign(['incoming_part_id']);
            $table->dropColumn('incoming_part_id');
        });
    }
};
