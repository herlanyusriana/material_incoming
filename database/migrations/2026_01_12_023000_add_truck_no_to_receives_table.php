<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            if (!Schema::hasColumn('receives', 'truck_no')) {
                $table->string('truck_no', 50)->nullable()->after('jo_po_number');
                $table->index('truck_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receives', function (Blueprint $table) {
            if (Schema::hasColumn('receives', 'truck_no')) {
                $table->dropIndex(['truck_no']);
                $table->dropColumn('truck_no');
            }
        });
    }
};

