<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('machine_name')->constrained('machines')->nullOnDelete();
            $table->dropColumn('machine_name');
        });

        Schema::table('production_planning_lines', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('machine_name')->constrained('machines')->nullOnDelete();
            $table->dropColumn('machine_name');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('machine_name')->constrained('machines')->nullOnDelete();
            $table->dropColumn('machine_name');
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_id');
            $table->string('machine_name')->nullable()->after('process_name');
        });

        Schema::table('production_planning_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_id');
            $table->string('machine_name')->nullable()->after('process_name');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_id');
            $table->string('machine_name')->nullable()->after('process_name');
        });
    }
};
