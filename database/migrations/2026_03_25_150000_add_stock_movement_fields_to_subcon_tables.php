<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subcon_orders')) {
            Schema::table('subcon_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('subcon_orders', 'send_location_code')) {
                    $table->string('send_location_code', 50)->nullable()->after('notes');
                }
                if (!Schema::hasColumn('subcon_orders', 'sent_posted_at')) {
                    $table->timestamp('sent_posted_at')->nullable()->after('send_location_code');
                }
                if (!Schema::hasColumn('subcon_orders', 'sent_posted_by')) {
                    $table->foreignId('sent_posted_by')->nullable()->after('sent_posted_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('subcon_order_receives')) {
            Schema::table('subcon_order_receives', function (Blueprint $table) {
                if (!Schema::hasColumn('subcon_order_receives', 'receive_location_code')) {
                    $table->string('receive_location_code', 50)->nullable()->after('received_date');
                }
                if (!Schema::hasColumn('subcon_order_receives', 'posted_to_wh_at')) {
                    $table->timestamp('posted_to_wh_at')->nullable()->after('receive_location_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subcon_order_receives')) {
            Schema::table('subcon_order_receives', function (Blueprint $table) {
                foreach (['posted_to_wh_at', 'receive_location_code'] as $column) {
                    if (Schema::hasColumn('subcon_order_receives', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('subcon_orders')) {
            Schema::table('subcon_orders', function (Blueprint $table) {
                if (Schema::hasColumn('subcon_orders', 'sent_posted_by')) {
                    $table->dropConstrainedForeignId('sent_posted_by');
                }
                foreach (['sent_posted_at', 'send_location_code'] as $column) {
                    if (Schema::hasColumn('subcon_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
