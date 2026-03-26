<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bin_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('bin_transfers', 'transfer_type')) {
                $table->string('transfer_type', 30)->nullable()->after('gci_part_id');
                $table->index('transfer_type');
            }
            if (!Schema::hasColumn('bin_transfers', 'from_batch_no')) {
                $table->string('from_batch_no')->nullable()->after('to_location_code');
            }
            if (!Schema::hasColumn('bin_transfers', 'to_batch_no')) {
                $table->string('to_batch_no')->nullable()->after('from_batch_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bin_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('bin_transfers', 'transfer_type')) {
                $table->dropIndex(['transfer_type']);
                $table->dropColumn('transfer_type');
            }
            if (Schema::hasColumn('bin_transfers', 'from_batch_no')) {
                $table->dropColumn('from_batch_no');
            }
            if (Schema::hasColumn('bin_transfers', 'to_batch_no')) {
                $table->dropColumn('to_batch_no');
            }
        });
    }
};
