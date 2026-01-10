<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            if (!Schema::hasColumn('parts', 'quality_inspection')) {
                $table->string('quality_inspection', 10)->nullable()->after('hs_code');
                $table->index('quality_inspection');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            if (Schema::hasColumn('parts', 'quality_inspection')) {
                $table->dropIndex(['quality_inspection']);
                $table->dropColumn('quality_inspection');
            }
        });
    }
};

