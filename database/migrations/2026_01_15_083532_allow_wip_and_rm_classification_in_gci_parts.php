<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove default value constraint and allow classification to be FG, WIP, or RM
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('classification', 10)->nullable()->change();
        });

        // Set existing NULL values to 'FG' for backwards compatibility
        DB::table('gci_parts')->whereNull('classification')->update(['classification' => 'FG']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('classification', 10)->default('FG')->change();
        });
    }
};
