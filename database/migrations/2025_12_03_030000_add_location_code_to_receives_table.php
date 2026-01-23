<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            if (!Schema::hasColumn('receives', 'location_code')) {
                $table->string('location_code')->nullable()->after('jo_po_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            if (Schema::hasColumn('receives', 'location_code')) {
                $table->dropColumn('location_code');
            }
        });
    }
};
