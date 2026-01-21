<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Try to drop the check constraint commonly created by Postgres for Enums.
        // We use a raw statement because Schema builder might not handle constraint dropping for enums directly.
        try {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        } catch (\Exception $e) {
            // If it fails, maybe it doesn't exist or different name. 
            // We ignore to proceed, or log it.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to restore exact check without raw SQL, skipping for now.
    }
};
