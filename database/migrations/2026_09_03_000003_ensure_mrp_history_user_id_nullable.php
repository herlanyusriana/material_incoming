<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ensure mrp_histories.user_id is nullable.
     *
     * The earlier alignment migration (2026_09_03_000002) only made user_id
     * nullable when the column did NOT already exist. On environments where the
     * column pre-exists as NOT NULL (a deploy-schema leftover), the global log
     * row in MrpController@clear inserts null via auth()->id(), and MySQL rejects
     * it with "Field 'user_id' doesn't have a default value" (1364). This
     * migration unconditionally relaxes user_id to nullable whenever present.
     */
    public function up(): void
    {
        Schema::table('mrp_histories', function (Blueprint $table) {
            if (Schema::hasColumn('mrp_histories', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            } else {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // No-op: re-enforcing NOT NULL would break global log rows.
    }
};
