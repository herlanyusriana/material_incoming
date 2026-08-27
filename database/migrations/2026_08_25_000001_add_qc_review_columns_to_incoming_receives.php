<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_receives', function (Blueprint $table) {
            $table->text('qc_note')->nullable()->after('qc_status');
            $table->timestamp('qc_updated_at')->nullable()->after('qc_audited_by');
            $table->unsignedBigInteger('qc_updated_by')->nullable()->after('qc_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_receives', function (Blueprint $table) {
            $table->dropColumn(['qc_note', 'qc_updated_at', 'qc_updated_by']);
        });
    }
};
