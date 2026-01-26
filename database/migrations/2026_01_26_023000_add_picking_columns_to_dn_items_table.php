<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dn_items')) {
            return;
        }

        Schema::table('dn_items', function (Blueprint $table) {
            if (!Schema::hasColumn('dn_items', 'picked_qty')) {
                $table->decimal('picked_qty', 18, 4)->default(0)->after('qty');
                $table->index('picked_qty');
            }
            if (!Schema::hasColumn('dn_items', 'picked_at')) {
                $table->timestamp('picked_at')->nullable()->after('picked_qty');
            }
            if (!Schema::hasColumn('dn_items', 'picked_by')) {
                $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete()->after('picked_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('dn_items')) {
            return;
        }

        Schema::table('dn_items', function (Blueprint $table) {
            if (Schema::hasColumn('dn_items', 'picked_by')) {
                $table->dropForeign(['picked_by']);
                $table->dropColumn('picked_by');
            }
            if (Schema::hasColumn('dn_items', 'picked_at')) {
                $table->dropColumn('picked_at');
            }
            if (Schema::hasColumn('dn_items', 'picked_qty')) {
                $table->dropIndex(['picked_qty']);
                $table->dropColumn('picked_qty');
            }
        });
    }
};

