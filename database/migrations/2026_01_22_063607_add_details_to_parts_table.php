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
        Schema::table('parts', function (Blueprint $table) {
            if (!Schema::hasColumn('parts', 'register_no')) {
                $table->string('register_no')->nullable()->after('part_no');
            }
            if (!Schema::hasColumn('parts', 'part_name_vendor')) {
                $table->string('part_name_vendor')->nullable()->after('part_name_gci');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('parts', 'register_no')) {
                $drops[] = 'register_no';
            }
            if (Schema::hasColumn('parts', 'part_name_vendor')) {
                $drops[] = 'part_name_vendor';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
