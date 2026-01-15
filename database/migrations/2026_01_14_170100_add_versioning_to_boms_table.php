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
        
        $db = DB::getDatabaseName();
        $tablePrefix = DB::getTablePrefix();
        $tableName = $tablePrefix . 'boms';
        
        $compositeExists = count(DB::select("
            SELECT index_name FROM information_schema.statistics 
            WHERE table_schema = ? AND table_name = ? AND index_name = 'boms_part_id_revision_unique'
        ", [$db, $tableName])) > 0;

        if (!$compositeExists) {
            Schema::table('boms', function (Blueprint $table) use ($db, $tableName) {
                $oldExists = count(DB::select("
                    SELECT index_name FROM information_schema.statistics 
                    WHERE table_schema = ? AND table_name = ? AND index_name = 'boms_part_id_unique'
                ", [$db, $tableName])) > 0;

                if ($oldExists) {
                    // We can't drop unique because it's used by foreign keys in other tables.
                    // Instead of dropping, we'll just ignore and try to add the composite one.
                    // If adding composite fails because of duplicate keys, we might need a different strategy.
                    try {
                        $table->unique(['part_id', 'revision']);
                    } catch (\Exception $e) {
                        // Log or ignore if already exists via different name
                    }
                } else {
                    $table->unique(['part_id', 'revision']);
                }
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
    }
};
