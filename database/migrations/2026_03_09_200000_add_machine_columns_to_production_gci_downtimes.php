<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_gci_downtimes', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('production_gci_work_order_id')->constrained('machines')->nullOnDelete();
            $table->string('machine_name')->nullable()->after('machine_id');
            $table->string('shift')->nullable()->after('machine_name');

            // Make work_order FK nullable (downtimes can now exist without a WO)
            $table->unsignedBigInteger('production_gci_work_order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_gci_downtimes', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn(['machine_id', 'machine_name', 'shift']);
        });
    }
};
