<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_planning_imports')) {
            return;
        }

        Schema::table('customer_planning_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_planning_imports', 'file_name')) {
                $table->string('file_name')->nullable()->after('original_filename');
            }
            if (!Schema::hasColumn('customer_planning_imports', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
            if (!Schema::hasColumn('customer_planning_imports', 'total_rows')) {
                $table->unsignedInteger('total_rows')->default(0)->after('error_message');
            }
            if (!Schema::hasColumn('customer_planning_imports', 'accepted_rows')) {
                $table->unsignedInteger('accepted_rows')->default(0)->after('total_rows');
            }
            if (!Schema::hasColumn('customer_planning_imports', 'rejected_rows')) {
                $table->unsignedInteger('rejected_rows')->default(0)->after('accepted_rows');
            }
            if (!Schema::hasColumn('customer_planning_imports', 'notes')) {
                $table->text('notes')->nullable()->after('rejected_rows');
            }
        });

        // Best-effort backfill for existing records.
        if (Schema::hasColumn('customer_planning_imports', 'original_filename') && Schema::hasColumn('customer_planning_imports', 'file_name')) {
            DB::table('customer_planning_imports')
                ->whereNull('file_name')
                ->update(['file_name' => DB::raw('original_filename')]);
        }
        if (Schema::hasColumn('customer_planning_imports', 'imported_by') && Schema::hasColumn('customer_planning_imports', 'uploaded_by')) {
            DB::table('customer_planning_imports')
                ->whereNull('uploaded_by')
                ->update(['uploaded_by' => DB::raw('imported_by')]);
        }
        if (Schema::hasColumn('customer_planning_imports', 'rows_imported') && Schema::hasColumn('customer_planning_imports', 'total_rows')) {
            DB::table('customer_planning_imports')
                ->where('total_rows', 0)
                ->update(['total_rows' => DB::raw('rows_imported')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_planning_imports')) {
            return;
        }

        Schema::table('customer_planning_imports', function (Blueprint $table) {
            if (Schema::hasColumn('customer_planning_imports', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('customer_planning_imports', 'rejected_rows')) {
                $table->dropColumn('rejected_rows');
            }
            if (Schema::hasColumn('customer_planning_imports', 'accepted_rows')) {
                $table->dropColumn('accepted_rows');
            }
            if (Schema::hasColumn('customer_planning_imports', 'total_rows')) {
                $table->dropColumn('total_rows');
            }
            if (Schema::hasColumn('customer_planning_imports', 'uploaded_by')) {
                $table->dropConstrainedForeignId('uploaded_by');
            }
            if (Schema::hasColumn('customer_planning_imports', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};

