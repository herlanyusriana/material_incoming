<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('default_location', 50)->nullable()->after('gross_weight');
        });
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->dropColumn('default_location');
        });
    }
};
