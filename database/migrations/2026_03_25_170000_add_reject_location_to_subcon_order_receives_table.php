<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcon_order_receives')) {
            return;
        }

        Schema::table('subcon_order_receives', function (Blueprint $table) {
            if (!Schema::hasColumn('subcon_order_receives', 'reject_location_code')) {
                $table->string('reject_location_code', 50)->nullable()->after('receive_location_code');
            }
            if (!Schema::hasColumn('subcon_order_receives', 'reject_posted_to_wh_at')) {
                $table->timestamp('reject_posted_to_wh_at')->nullable()->after('posted_to_wh_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subcon_order_receives')) {
            return;
        }

        Schema::table('subcon_order_receives', function (Blueprint $table) {
            foreach (['reject_posted_to_wh_at', 'reject_location_code'] as $column) {
                if (Schema::hasColumn('subcon_order_receives', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
