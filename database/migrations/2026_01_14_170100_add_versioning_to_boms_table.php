<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->string('revision', 10)->default('A')->after('part_id');
            $table->date('effective_date')->default(now())->after('revision');
            $table->date('end_date')->nullable()->after('effective_date');
            $table->text('change_reason')->nullable()->after('end_date');
            
            $table->index('effective_date');
        });
        
        // Handle unique constraint separately to avoid FK issues
        Schema::table('boms', function (Blueprint $table) {
            $table->dropUnique(['part_id']);
        });
        
        Schema::table('boms', function (Blueprint $table) {
            $table->unique(['part_id', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropUnique(['part_id', 'revision']);
        });
        
        Schema::table('boms', function (Blueprint $table) {
            $table->unique(['part_id']);
            $table->dropColumn(['revision', 'effective_date', 'end_date', 'change_reason']);
        });
    }
};
