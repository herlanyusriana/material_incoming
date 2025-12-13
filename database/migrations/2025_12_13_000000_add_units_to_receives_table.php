<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table->string('bundle_unit', 20)->nullable()->after('qty')->comment('Unit for bundle quantity (Coil, Pallet, Box, Pcs)');
            $table->string('qty_unit', 20)->nullable()->after('weight')->comment('Unit for total quantity (KGM, PCS, SHEET)');
        });
    }

    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table->dropColumn(['bundle_unit', 'qty_unit']);
        });
    }
};

