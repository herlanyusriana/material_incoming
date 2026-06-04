<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('gci_parts', 'subcount_enabled')) {
                $table->boolean('subcount_enabled')->default(false)->after('consumption_policy');
            }
            if (!Schema::hasColumn('gci_parts', 'subcount_document_no')) {
                $table->string('subcount_document_no', 100)->nullable()->after('subcount_enabled');
            }
            if (!Schema::hasColumn('gci_parts', 'subcount_qty')) {
                $table->unsignedInteger('subcount_qty')->nullable()->after('subcount_document_no');
            }
            if (!Schema::hasColumn('gci_parts', 'subcount_uom')) {
                $table->string('subcount_uom', 20)->default('PCE')->after('subcount_qty');
            }
            if (!Schema::hasColumn('gci_parts', 'subcount_process_type')) {
                $table->string('subcount_process_type', 50)->default('PG')->after('subcount_uom');
            }
        });

        DB::table('gci_parts')
            ->where('part_no', 'MAZ65643601-PG')
            ->update([
                'subcount_enabled' => true,
                'subcount_document_no' => '--[2025-06-23',
                'subcount_qty' => 10000,
                'subcount_uom' => 'PCE',
                'subcount_process_type' => 'PG',
            ]);

        DB::table('gci_parts')
            ->where('part_no', 'MFA62123401-PG')
            ->update([
                'subcount_enabled' => true,
                'subcount_document_no' => '--[2025-11-20',
                'subcount_qty' => 20000,
                'subcount_uom' => 'PCE',
                'subcount_process_type' => 'PG',
            ]);
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            foreach ([
                'subcount_process_type',
                'subcount_uom',
                'subcount_qty',
                'subcount_document_no',
                'subcount_enabled',
            ] as $column) {
                if (Schema::hasColumn('gci_parts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
