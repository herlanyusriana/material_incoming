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
        // For Postgres, we need to drop the check constraint first if it exists.
        // It's usually named table_column_check.
        // But to be DB agnostic friendly for "change", we'll just try to change it to string.
        // If it fails on Postgres due to constraint, we might need raw SQL. 
        // Let's try standard change first, but "enum" to "string" is tricky.

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting back to enum might be lossy if we have other roles, 
        // but for strict down we define the original enum.
        Schema::table('users', function (Blueprint $table) {
            // $table->enum('role', ['admin', 'staff'])->default('staff')->change();
        });
    }
};
