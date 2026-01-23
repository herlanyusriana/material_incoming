<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (!Schema::hasColumn('arrivals', 'trucking_company_id')) {
                $table->foreignId('trucking_company_id')->nullable()->after('vendor_id')->constrained('trucking_companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('arrivals', 'container_numbers')) {
                $table->text('container_numbers')->nullable()->after('eta_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            if (Schema::hasColumn('arrivals', 'trucking_company_id')) {
                try {
                    $table->dropForeign(['trucking_company_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('trucking_company_id');
            }
            if (Schema::hasColumn('arrivals', 'container_numbers')) {
                $table->dropColumn('container_numbers');
            }
        });
    }
};
