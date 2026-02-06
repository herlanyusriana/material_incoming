<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outgoing_jig_settings', function (Blueprint $table) {
            // Drop customer_id relation
            $table->dropUnique(['line', 'customer_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');

            // Add customer_part_id
            $table->foreignId('customer_part_id')->after('line')->constrained('customer_parts')->cascadeOnDelete();

            // Add new unique index
            $table->unique(['line', 'customer_part_id'], 'jig_settings_line_part_unique');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_jig_settings', function (Blueprint $table) {
            $table->dropUnique('jig_settings_line_part_unique');
            $table->dropForeign(['customer_part_id']);
            $table->dropColumn('customer_part_id');

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unique(['line', 'customer_id']);
        });
    }
};
