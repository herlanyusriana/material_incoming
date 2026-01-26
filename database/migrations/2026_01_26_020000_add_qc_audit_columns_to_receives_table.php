<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receives')) {
            return;
        }

        Schema::table('receives', function (Blueprint $table) {
            if (!Schema::hasColumn('receives', 'qc_note')) {
                $table->text('qc_note')->nullable()->after('qc_status');
            }
            if (!Schema::hasColumn('receives', 'qc_updated_at')) {
                $table->timestamp('qc_updated_at')->nullable()->after('qc_note');
            }
            if (!Schema::hasColumn('receives', 'qc_updated_by')) {
                $table->foreignId('qc_updated_by')->nullable()->constrained('users')->nullOnDelete()->after('qc_updated_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('receives')) {
            return;
        }

        Schema::table('receives', function (Blueprint $table) {
            if (Schema::hasColumn('receives', 'qc_updated_by')) {
                $table->dropForeign(['qc_updated_by']);
                $table->dropColumn('qc_updated_by');
            }
            if (Schema::hasColumn('receives', 'qc_updated_at')) {
                $table->dropColumn('qc_updated_at');
            }
            if (Schema::hasColumn('receives', 'qc_note')) {
                $table->dropColumn('qc_note');
            }
        });
    }
};

