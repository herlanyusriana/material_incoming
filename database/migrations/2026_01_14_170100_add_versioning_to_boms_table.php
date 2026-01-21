<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            if (!Schema::hasColumn('boms', 'revision')) {
                $table->string('revision', 10)->default('A')->after('part_id');
            }
            if (!Schema::hasColumn('boms', 'effective_date')) {
                $table->date('effective_date')->default(now()->format('Y-m-d'))->after('revision');
                $table->index('effective_date');
            }
            if (!Schema::hasColumn('boms', 'end_date')) {
                $table->date('end_date')->nullable()->after('effective_date');
            }
            if (!Schema::hasColumn('boms', 'change_reason')) {
                $table->text('change_reason')->nullable()->after('end_date');
            }
        });

        // Use a simpler approach for composite unique that works across drivers
        try {
            Schema::table('boms', function (Blueprint $table) {
                $table->unique(['part_id', 'revision']);
            });
        } catch (\Exception $e) {
            // Probably already exists
        }
    }

    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            if (Schema::hasColumn('boms', 'revision')) {
                try {
                    $table->dropUnique(['part_id', 'revision']);
                } catch (\Exception $e) {
                }
            }
        });
    }
};
