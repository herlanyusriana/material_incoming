<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcon_orders')) {
            return;
        }

        Schema::table('subcon_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('subcon_orders', 'rm_gci_part_id')) {
                $table->foreignId('rm_gci_part_id')
                    ->nullable()
                    ->after('vendor_id')
                    ->constrained('gci_parts');
            }
        });

        DB::table('subcon_orders')
            ->whereNull('rm_gci_part_id')
            ->update([
                'rm_gci_part_id' => DB::raw('gci_part_id'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('subcon_orders') || !Schema::hasColumn('subcon_orders', 'rm_gci_part_id')) {
            return;
        }

        Schema::table('subcon_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rm_gci_part_id');
        });
    }
};
