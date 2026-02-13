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
        Schema::table('standard_packings', function (Blueprint $table) {
            $table->decimal('net_weight', 10, 4)->nullable()->after('uom');
            $table->decimal('gross_weight', 10, 4)->nullable()->after('net_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('standard_packings', function (Blueprint $table) {
            //
        });
    }
};
