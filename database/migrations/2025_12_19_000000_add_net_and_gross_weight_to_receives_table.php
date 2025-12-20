<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table->decimal('net_weight', 8, 2)->nullable()->after('weight');
            $table->decimal('gross_weight', 8, 2)->nullable()->after('net_weight');
        });
    }

    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            $table->dropColumn(['net_weight', 'gross_weight']);
        });
    }
};

