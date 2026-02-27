<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->renameColumn('sort_order', 'cycle_time');
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->decimal('cycle_time', 10, 2)->default(0)->change();
            $table->string('cycle_time_unit', 20)->default('seconds')->after('cycle_time');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('cycle_time_unit');
            $table->renameColumn('cycle_time', 'sort_order');
        });
    }
};
