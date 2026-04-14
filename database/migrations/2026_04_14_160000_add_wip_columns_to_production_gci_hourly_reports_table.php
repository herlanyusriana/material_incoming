<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->string('output_type', 20)->default('fg')->after('shift');
            $table->string('process_name')->nullable()->after('output_type');
            $table->string('output_part_no')->nullable()->after('process_name');
            $table->string('output_part_name')->nullable()->after('output_part_no');
        });
    }

    public function down(): void
    {
        Schema::table('production_gci_hourly_reports', function (Blueprint $table) {
            $table->dropColumn([
                'output_type',
                'process_name',
                'output_part_no',
                'output_part_name',
            ]);
        });
    }
};
