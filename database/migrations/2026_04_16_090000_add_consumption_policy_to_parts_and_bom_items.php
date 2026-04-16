<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('gci_parts', 'consumption_policy')) {
                $table->string('consumption_policy', 40)
                    ->default('backflush_return')
                    ->after('is_backflush');
            }
            if (!Schema::hasColumn('gci_parts', 'policy_confirmed_at')) {
                $table->timestamp('policy_confirmed_at')->nullable()->after('consumption_policy');
            }
            if (!Schema::hasColumn('gci_parts', 'policy_confirmed_by')) {
                $table->foreignId('policy_confirmed_by')
                    ->nullable()
                    ->after('policy_confirmed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'consumption_policy_override')) {
                $table->string('consumption_policy_override', 40)->nullable()->after('make_or_buy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (Schema::hasColumn('bom_items', 'consumption_policy_override')) {
                $table->dropColumn('consumption_policy_override');
            }
        });

        Schema::table('gci_parts', function (Blueprint $table) {
            if (Schema::hasColumn('gci_parts', 'policy_confirmed_by')) {
                $table->dropConstrainedForeignId('policy_confirmed_by');
            }
            if (Schema::hasColumn('gci_parts', 'policy_confirmed_at')) {
                $table->dropColumn('policy_confirmed_at');
            }
            if (Schema::hasColumn('gci_parts', 'consumption_policy')) {
                $table->dropColumn('consumption_policy');
            }
        });
    }
};
