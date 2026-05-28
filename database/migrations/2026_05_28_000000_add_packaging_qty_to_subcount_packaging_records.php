<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcount_packaging_records')) {
            return;
        }

        Schema::table('subcount_packaging_records', function (Blueprint $table) {
            if (!Schema::hasColumn('subcount_packaging_records', 'packaging_qty')) {
                $table->unsignedInteger('packaging_qty')->default(0)->after('packaging_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subcount_packaging_records')) {
            return;
        }

        Schema::table('subcount_packaging_records', function (Blueprint $table) {
            if (Schema::hasColumn('subcount_packaging_records', 'packaging_qty')) {
                $table->dropColumn('packaging_qty');
            }
        });
    }
};
