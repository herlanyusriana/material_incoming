<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('last_handover_from_process')->nullable()->after('process_name');
            $table->unsignedBigInteger('last_handover_from_machine_id')->nullable()->after('last_handover_from_process');
            $table->string('last_handover_from_machine_name')->nullable()->after('last_handover_from_machine_id');
            $table->timestamp('last_handover_at')->nullable()->after('last_handover_from_machine_name');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'last_handover_from_process',
                'last_handover_from_machine_id',
                'last_handover_from_machine_name',
                'last_handover_at',
            ]);
        });
    }
};
