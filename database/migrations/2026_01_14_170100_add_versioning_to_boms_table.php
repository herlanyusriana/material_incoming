<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            if (!Schema::hasColumn('boms', 'revision')) {
                $table->string('revision', 10)->default('A')->after('part_id');
            }
            if (!Schema::hasColumn('boms', 'effective_date')) {
                $table->date('effective_date')->default(now())->after('revision');
                $table->index('effective_date');
            }
            if (!Schema::hasColumn('boms', 'end_date')) {
                $table->date('end_date')->nullable()->after('effective_date');
            }
            if (!Schema::hasColumn('boms', 'change_reason')) {
                $table->text('change_reason')->nullable()->after('end_date');
            }
        });
        
        // Handle unique constraint separately
        // We check if the composite unique exists by querying information_schema
        $db = DB::getDatabaseName();
        $table = DB::getTablePrefix() . 'boms';
        
        $compositeExists = count(DB::select("
            SELECT index_name FROM information_schema.statistics 
            WHERE table_schema = ? AND table_name = ? AND index_name = 'boms_part_id_revision_unique'
        ", [$db, $table])) > 0;

        if (!$compositeExists) {
            Schema::table('boms', function (Blueprint $table) {
                // Drop old single unique if it exists
                $db = DB::getDatabaseName();
                $table_name = DB::getTablePrefix() . 'boms';
                $oldExists = count(DB::select("
                    SELECT index_name FROM information_schema.statistics 
                    WHERE table_schema = ? AND table_name = ? AND index_name = 'boms_part_id_unique'
                ", [$db, $table_name])) > 0;

                if ($oldExists) {
                    $table->dropUnique(['part_id']);
                }
                
                $table->unique(['part_id', 'revision']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            if (Schema::hasColumn('boms', 'revision')) {
                $table->dropUnique(['part_id', 'revision']);
            }
        });
        
        Schema::table('boms', function (Blueprint $table) {
            $table->unique(['part_id']);
            $table->dropColumn(['revision', 'effective_date', 'end_date', 'change_reason']);
        });
    }
};
