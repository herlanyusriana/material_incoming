<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('barcode', 100)->nullable()->unique()->after('part_no');
        });
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};
