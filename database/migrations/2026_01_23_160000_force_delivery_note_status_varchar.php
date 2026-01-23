<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('delivery_notes') || !Schema::hasColumn('delivery_notes', 'status')) {
            return;
        }

        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE `delivery_notes` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // No safe rollback: previous type/length is unknown.
    }
};

