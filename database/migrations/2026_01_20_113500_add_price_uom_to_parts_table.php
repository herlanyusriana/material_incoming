<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('quality_inspection');
            $table->string('uom', 10)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn(['price', 'uom']);
        });
    }
};
