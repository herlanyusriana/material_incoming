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
        Schema::table('gci_parts', function (Blueprint $blueprint) {
            $blueprint->foreignId('customer_id')->nullable()->after('id')->constrained('customers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['customer_id']);
            $blueprint->dropColumn('customer_id');
        });
    }
};
