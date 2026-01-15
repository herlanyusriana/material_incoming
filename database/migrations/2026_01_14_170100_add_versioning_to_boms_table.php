<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            }
            if (!Schema::hasColumn('boms', 'end_date')) {
                $table->date('end_date')->nullable()->after('effective_date');
            }
            if (!Schema::hasColumn('boms', 'change_reason')) {
                $table->text('change_reason')->nullable()->after('end_date');
            }
            
            // Re-check for index to be safe
            $conn = Schema::getConnection();
            $indexes = $conn->getDoctrineSchemaManager()->listTableIndexes('boms');
            if (!array_key_exists('boms_effective_date_index', $indexes)) {
                $table->index('effective_date');
            }
        });
        
        // Handle unique constraint separately
        Schema::table('boms', function (Blueprint $table) {
            $conn = Schema::getConnection();
            $indexes = $conn->getDoctrineSchemaManager()->listTableIndexes('boms');
            
            // Check if old unique exists before dropping
            if (array_key_exists('boms_part_id_unique', $indexes)) {
                $table->dropUnique(['part_id']);
            }
        });
        
        Schema::table('boms', function (Blueprint $table) {
            $conn = Schema::getConnection();
            $indexes = $conn->getDoctrineSchemaManager()->listTableIndexes('boms');
            
            // Add new unique if it doesn't exist
            if (!array_key_exists('boms_part_id_revision_unique', $indexes)) {
                $table->unique(['part_id', 'revision']);
            }
        });
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
