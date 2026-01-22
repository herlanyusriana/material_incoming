<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Add column as nullable first
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('name');
            }
        });

        // 2. Populate existing users
        DB::table('users')->whereNull('username')->orWhere('username', '')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                // Generate a base username from email or name
                $base = explode('@', $user->email)[0] ?? Str::slug($user->name);
                $newUsername = $base;
                $counter = 1;

                // Ensure uniqueness
                while (DB::table('users')->where('username', $newUsername)->where('id', '!=', $user->id)->exists()) {
                    $newUsername = $base . $counter++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $newUsername]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // 3. Make it required and unique
            $table->string('username')->nullable(false)->change();
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
