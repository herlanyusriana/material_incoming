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
        Schema::table('parts', function (Blueprint $table) {
            // Establish the bridge: Link Vendor Part (Child) to Internal GCI Part (Parent)
            $table->foreignId('gci_part_id')
                ->nullable()
                ->after('part_name_gci') // Place nicely after the name
                ->constrained('gci_parts')
                ->onDelete('set null'); // If Master Part deleted, don't delete history, just unlink
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign(['gci_part_id']);
            $table->dropColumn('gci_part_id');
        });
    }
};
