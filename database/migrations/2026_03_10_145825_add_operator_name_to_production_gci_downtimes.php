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
        Schema::table('production_gci_downtimes', function (Blueprint $table) {
            $table->string('operator_name')->nullable()->after('shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_gci_downtimes', function (Blueprint $table) {
            $table->dropColumn('operator_name');
        });
    }
};
